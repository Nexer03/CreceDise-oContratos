<?php
session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Auth Check
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$usuarioId = $_SESSION['usuario_id'];
$catalog = require __DIR__ . '/config/catalog.php';

// --- Admin flag (no rompe nada) ---
$isAdmin = 0;

// Si ya lo guardas en sesión, úsalo
if (isset($_SESSION['is_admin'])) {
    $isAdmin = (int)$_SESSION['is_admin'];
} else {
    // Si no, lo consultamos a DB (fallback seguro)
    try {
        $stAdmin = $pdo->prepare("SELECT is_admin FROM usuarios WHERE id = :id LIMIT 1");
        $stAdmin->execute([':id' => $usuarioId]);
        $rowAdmin = $stAdmin->fetch(PDO::FETCH_ASSOC);
        $isAdmin = (int)($rowAdmin['is_admin'] ?? 0);
        $_SESSION['is_admin'] = $isAdmin; // cache en sesión
    } catch (PDOException $e) {
        $isAdmin = 0; // si falla, mejor no dar acceso
    }
}

// Fetch Payments
$payments = [];
$error = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE usuario_id = :uid ORDER BY id DESC");
    $stmt->execute([':uid' => $usuarioId]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Crece Diseño - Historial de Compras</title>

  <!-- Tipografías -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- AOS (animaciones) -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet" />

  <!-- CSS principal -->
  <link rel="stylesheet" href="styles.css" />
  <link rel="stylesheet" href="contratos.css" />
  
  <style>
      body {
          background-color: #f5f7fa;
      }
      .history-container {
          padding: 80px 0 80px;
          min-height: 50vh;
          background-color: #ffffff;
          background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23cbd5e1' fill-opacity='0.25'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
          position: relative;
          z-index: 1;
      }
      .table-card {
          background: white;
          border-radius: 18px;
          box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08), 0 2px 10px rgba(0, 0, 0, 0.04);
          overflow: hidden;
          padding: 30px;
          border: 1px solid rgba(0, 0, 0, 0.05);
      }
      .status-badge {
          padding: 5px 12px;
          border-radius: 20px;
          font-size: 0.85em;
          font-weight: 600;
          text-transform: uppercase;
      }
      .status-completed {
          background-color: #d4edda;
          color: #155724;
      }
      .status-pending {
          background-color: #fff3cd;
          color: #856404;
      }
      .status-failed {
          background-color: #f8d7da;
          color: #721c24;
      }
      .btn-back {
          background-color: var(--dark-blue);
          color: white;
          border: none;
          padding: 10px 20px;
          border-radius: 5px;
          text-decoration: none;
          transition: background 0.3s ease;
      }
      .btn-back:hover {
          background-color: #217CE3;
          color: white;
      }
      
      /* Navbar User Dropdown Style */
      .user-profile-menu {
        position: relative;
        margin-left: 15px;
      }
      .user-toggle {
        background: none;
        border: 2px solid #217CE3;
        border-radius: 30px;
        padding: 5px 15px;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        color: #217CE3;
        font-weight: 600;
        transition: all 0.3s ease;
      }
      .user-toggle:hover {
        background: #217CE3;
        color: white;
      }
      .user-dropdown {
        position: absolute;
        top: 120%;
        right: 0;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        width: 280px;
        padding: 15px;
        display: none; /* hidden by default */
        flex-direction: column;
        z-index: 1000;
      }
      .user-dropdown.active {
        display: flex;
      }
      .user-info {
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
        margin-bottom: 10px;
      }
      .user-name {
        display: block;
        font-weight: 700;
        color: #1A1C36;
      }
      .user-email {
        font-size: 0.85rem;
        color: #666;
      }
      .logout-btn {
        color: #dc3545;
        text-decoration: none;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 10px;
        border-radius: 6px;
        transition: background 0.2s;
        margin-top: 5px;
      }
      .logout-btn:hover {
        background: #fff5f5;
      }
      .history-btn {
        color: var(--dark-blue);
        text-decoration: none;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 10px;
        border-radius: 6px;
        transition: background 0.2s;
      }
      .history-btn:hover {
        background: #f0f4f8;
        color: #217CE3;
      }

      /* (opcional) estilo igual al history-btn para admin */
      .admin-btn {
        color: var(--dark-blue);
        text-decoration: none;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 10px;
        border-radius: 6px;
        transition: background 0.2s;
      }
      .admin-btn:hover {
        background: #f0f4f8;
        color: #217CE3;
      }
      /* Eliminando el padding-top global para que el hero se ajuste al navbar transparente */
  </style>
</head>

<body>

  <!-- BACKGROUND -->
  <div class="background-container"></div>

  <!-- NAVBAR -->
  <header class="shadow-sm">
    <nav class="navbar navbar-expand-lg bg-white-95 fixed-top custom-navbar" id="mainNavbar">
      <div class="container-fluid px-2 px-sm-3 px-lg-4">

        <a class="navbar-brand d-flex align-items-center me-auto brand-left" href="index.php">
          <img src="logo.svg" alt="Crece Diseño" class="brand-logo" />
        </a>

        <button class="navbar-toggler ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
          aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
          <ul class="navbar-nav ms-auto align-items-lg-center">
            <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
            <li class="nav-item"><a class="nav-link" href="cursos.php">Cursos</a></li>
            <li class="nav-item"><a class="nav-link" href="contratos.php">Contratos</a></li>
            <li class="nav-item"><a class="nav-link" href="nosotros.php">Nosotros</a></li>
            <li class="nav-item"><a class="nav-link" href="index.php#contacto">Contacto</a></li>

            <?php if(isset($_SESSION['usuario_id'])): ?>
            <li class="nav-item user-profile-menu">
              <button class="user-toggle" aria-expanded="false">
                <i class="fas fa-user-circle"></i>
                <span>Hola, <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></span>
                <i class="fas fa-chevron-down small"></i>
              </button>
              <div class="user-dropdown">
                <div class="user-info">
                  <span class="user-name"><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></span>
                  <?php if(isset($_SESSION['usuario_correo'])): ?>
                    <span class="user-email"><?php echo htmlspecialchars($_SESSION['usuario_correo']); ?></span>
                  <?php endif; ?>
                </div>

                <?php if(!empty($isAdmin)): ?>
                  <a href="admin_analitica.php" class="admin-btn">
                    <i class="fas fa-chart-line"></i> Panel Admin
                  </a>
                <?php endif; ?>
                
                <a href="analitica.php" class="history-btn">
                  <i class="fas fa-receipt"></i> Historial de Compras
                </a>
                
                <a href="config/logout.php" class="logout-btn">
                  <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
              </div>
            </li>
            <?php else: ?>
            <li class="nav-item ms-lg-2">
                 <a href="index.php" class="btn btn-primary btn-cta">Iniciar Sesión</a>
            </li>
            <?php endif; ?>
          </ul>
        </div>

      </div>
    </nav>
  </header>

  <!-- LISTADO DE COMPRAS -->
  <section class="contracts-hero" id="inicio">
    <div class="banner-container"><img src="patron1.svg" alt="Patrón de fondo" /></div>
    <div class="container">
      <div class="contracts-hero-content center-block" data-aos="fade-up" data-aos-duration="800">
        <h1 class="contracts-main-title">Tus Contratos <span class="highlight-gradient">Adquiridos</span> <i class="fa-solid fa-receipt title-icon"></i></h1>
        <p style="font-size: 1.15rem; color: rgba(255, 255, 255, 0.95); max-width: 800px; margin: 0 auto 2rem;">
          Aquí tienes un registro de todos los contratos que has comprado.
          Puedes acceder a ellos en cualquier momento para llenarlos y descargar su versión final en PDF sin marcas.
        </p>
        <div class="d-flex gap-2 justify-content-center">
          <a href="contratos.php" class="btn btn-primary btn-cta">Ver más contratos</a>
        </div>
      </div>
    </div>
  </section>

  <section class="history-container">
      <div class="container">
          <div class="table-card" data-aos="fade-up" data-aos-delay="100">
              <?php if (isset($error)): ?>
                  <div class="alert alert-danger">
                      <h4 class="alert-heading">Error de Conexión</h4>
                      <p>No se pudo obtener el historial. Detalle: <?php echo htmlspecialchars($error); ?></p>
                  </div>
              <?php elseif (empty($payments)): ?>
                  <div class="text-center py-5">
                      <div class="mb-4">
                        <i class="fas fa-shopping-cart fa-4x" style="color: #ddd;"></i>
                      </div>
                      <h3 class="mb-3">Aún no has realizado compras</h3>
                      <p class="text-muted mb-4">Visita nuestro catálogo de contratos para empezar.</p>
                      <a href="contratos.php" class="btn-back">
                          Ver Contratos
                      </a>
                  </div>
              <?php else: ?>
                  <div class="table-responsive">
                      <table class="table table-hover align-middle">
                          <thead class="table-light">
                              <tr>
                                  <th scope="col" style="min-width: 250px;">Producto</th>
                                  <th scope="col">Precio</th>
                                  <th scope="col">Fecha</th>
                                  <th scope="col">Estado</th>
                                  <th scope="col">Acciones</th>
                              </tr>
                          </thead>
                          <tbody>
                              <?php foreach ($payments as $pay): 
                                  $productKey = $pay['product'] ?? '';
                                  $title = $catalog[$productKey]['title'] ?? ucfirst(str_replace('_', ' ', $productKey));
                                  if (empty($title)) $title = "Producto Desconocido";

                                  $amount = $pay['amount'] ?? 0;
                                  $currency = $pay['currency'] ?? 'MXN';
                                  $price = number_format((float)$amount, 2);
                                  
                                  $statusOriginal = $pay['status'] ?? 'UNKNOWN';
                                  $status = strtoupper($statusOriginal);
                                  
                                  $dateStr = 'Fecha no registrada';
                                  if (!empty($pay['created_at'])) {
                                      $dateStr = date('d/m/Y H:i', strtotime($pay['created_at']));
                                  } elseif (!empty($pay['fecha'])) {
                                       $dateStr = date('d/m/Y H:i', strtotime($pay['fecha']));
                                  }

                                  $statusClass = 'status-pending';
                                  $statusText = 'Pendiente';
                                  
                                  if ($status === 'COMPLETED') {
                                      $statusClass = 'status-completed';
                                      $statusText = 'Pagado';
                                  }
                                  elseif ($status === 'FAILED' || $status === 'DENIED') {
                                      $statusClass = 'status-failed';
                                      $statusText = 'Fallido';
                                  }
                              ?>
                              <tr>
                                  <td>
                                      <div class="d-flex align-items-center">
                                          <div class="me-3 bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; color: var(--dark-blue);">
                                              <i class="fas fa-file-contract"></i>
                                          </div>
                                          <div>
                                              <strong><?php echo htmlspecialchars($title); ?></strong>
                                              <div class="small text-muted">ID Orden: <?php echo htmlspecialchars($pay['order_id'] ?? 'N/A'); ?></div>
                                          </div>
                                      </div>
                                  </td>
                                  <td><strong>$<?php echo $price . ' ' . $currency; ?></strong></td>
                                  <td><?php echo $dateStr; ?></td>
                                  <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?> (<?php echo $statusOriginal; ?>)</span></td>
                                  <td>
                                      <a href="contratos.php" class="btn btn-sm btn-outline-primary rounded-pill">
                                          <i class="fas fa-download me-1"></i> Ir a Descargas
                                      </a>
                                  </td>
                              </tr>
                              <?php endforeach; ?>
                          </tbody>
                      </table>
                  </div>
              <?php endif; ?>
          </div>
      </div>
  </section>

  <!-- Footer (ligero, consistente) -->
  <footer>
    <div class="container">
      <div class="footer-container">
        <div class="footer-col">
          <h3>Crece Diseño</h3>
          <p>Contratos editables listos para descarga en PDF.</p>
          <div class="social-links social-centered">
            <a href="https://www.instagram.com/crece_diseno?igsh=MWRtNHlvaGs4dmt0dA==" class="social-link"
              target="_blank" rel="noopener" aria-label="Instagram">
              <i class="fab fa-instagram"></i>
            </a>
            <a href="https://www.tiktok.com/@mambeturouch?_r=1&_t=ZS-918cYzJJefC" class="social-link" target="_blank"
              rel="noopener" aria-label="TikTok">
              <i class="fab fa-tiktok"></i>
            </a>
          </div>
        </div>

        <div class="footer-col">
          <h3>Enlaces</h3>
          <a href="index.php">Inicio</a>
          <a href="cursos.php">Cursos</a>
          <a href="contratos.php">Contratos</a>
          <a href="nosotros.php">Nosotros</a>
          <a href="index.php#contacto">Contacto</a>
        </div>

        <div class="footer-col">
          <h3>Contratos</h3>
          <a href="contratos.php">Listado</a>
          <a href="contratoPRESTACIONDESERVICIOS.html">Prestación de servicios</a>
          <a href="contrato%20CESIONDEDERECHOS.html">Cesión de derechos</a>
        </div>
      </div>
    </div>
  </footer>

  <!-- SCRIPTS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
  <script>
      AOS.init();
      
      // Toggle Menu Logic
      document.addEventListener('DOMContentLoaded', function() {
          const userToggle = document.querySelector('.user-toggle');
          const userDropdown = document.querySelector('.user-dropdown');
          
          if(userToggle && userDropdown) {
              userToggle.addEventListener('click', function(e) {
                  e.stopPropagation();
                  userDropdown.classList.toggle('active');
                  const expanded = userDropdown.classList.contains('active');
                  userToggle.setAttribute('aria-expanded', expanded);
              });

              document.addEventListener('click', function(e) {
                  if (!userDropdown.contains(e.target) && !userToggle.contains(e.target)) {
                      userDropdown.classList.remove('active');
                      userToggle.setAttribute('aria-expanded', 'false');
                  }
              });
          }
      });
  </script>
  <script>
    // Efecto navbar al hacer scroll
    (function () {
      const navbar = document.getElementById('mainNavbar');
      let lastScrollTop = 0;

      window.addEventListener('scroll', () => {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

        if (scrollTop > 100) navbar.classList.add('navbar-scrolled');
        else navbar.classList.remove('navbar-scrolled');

        if (scrollTop > lastScrollTop && scrollTop > 100) navbar.classList.add('navbar-hidden');
        else navbar.classList.remove('navbar-hidden');

        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
      }, { passive: true });
    })();
  </script>
</body>
</html>