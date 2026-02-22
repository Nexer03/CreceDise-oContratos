<?php
session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/admin_flag.php';

// Auth + Admin check
if (!isset($_SESSION['usuario_id'])) {
  header("Location: index.php");
  exit;
}
if (empty($_SESSION['is_admin'])) {
  http_response_code(403);
  die("Acceso denegado.");
}

// Path del catálogo (intenta config/ y fallback a raíz)
$catalogPath = __DIR__ . '/config/catalog.php';
if (!file_exists($catalogPath)) {
  $catalogPath = __DIR__ . '/catalog.php';
}

$catalog = require $catalogPath;

// CSRF token
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_token'];

$saveOk = null;
$saveErr = null;

function normalize_price($value) {
  $value = trim((string)$value);
  $value = str_replace(',', '.', $value);

  if (!preg_match('/^\d+(\.\d{1,2})?$/', $value)) return null;

  $num = (float)$value;
  if ($num < 0 || $num > 100000) return null;

  return number_format($num, 2, '.', '');
}

function write_catalog_file($path, array $catalog) {
  // Mantén short array syntax como tu archivo original
  $lines = [];
  $lines[] = "<?php";
  $lines[] = "return [";

  foreach ($catalog as $key => $item) {
    $k = addslashes($key);
    $price = addslashes((string)($item['price'] ?? '0.00'));
    $title = addslashes((string)($item['title'] ?? $key));
    $lines[] = "  '{$k}' => ['price' => '{$price}', 'title' => '{$title}'],";
  }

  $lines[] = "];";
  $content = implode("\n", $lines) . "\n";

  // Escritura atómica
  $tmp = $path . '.tmp';
  if (file_put_contents($tmp, $content, LOCK_EX) === false) {
    return "No se pudo escribir el archivo temporal.";
  }
  if (!rename($tmp, $path)) {
    @unlink($tmp);
    return "No se pudo reemplazar el archivo del catálogo.";
  }
  return null;
}

// Guardar cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['csrf_token'] ?? '';
  if (!hash_equals($csrf, $token)) {
    $saveErr = "Token inválido. Recarga la página e intenta de nuevo.";
  } else {
    $prices = $_POST['price'] ?? [];
    $changed = 0;

    foreach ($catalog as $key => $item) {
      if (!array_key_exists($key, $prices)) continue;

      $newPrice = normalize_price($prices[$key]);
      if ($newPrice === null) {
        $saveErr = "Precio inválido en: {$key}. Usa formato tipo 199 o 199.00";
        break;
      }

      if (($catalog[$key]['price'] ?? '') !== $newPrice) {
        $catalog[$key]['price'] = $newPrice;
        $changed++;
      }
    }

    if (!$saveErr) {
      $err = write_catalog_file($catalogPath, $catalog);
      if ($err) $saveErr = $err;
      else $saveOk = ($changed > 0) ? "Listo: se guardaron {$changed} cambios." : "Sin cambios que guardar.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Crece Diseño - Admin Catálogo</title>

  <!-- Tipografías -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- AOS -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet" />
  <!-- CSS principal -->
  <link rel="stylesheet" href="styles.css" />
  <link rel="stylesheet" href="contratos.css" />

  <style>
    body { background-color: #f5f7fa; }
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
    .price-input { max-width: 140px; }
    .muted { color:#6c757d; font-size:.9rem; }
  </style>
</head>

<body>
  <div class="background-container"></div>

  <!-- NAVBAR (usa el mismo patrón que tu analitica.php) -->
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
                <?php if (!empty($isAdmin)): ?>
                <a href="admin_analitica.php" class="admin-btn">
                  <i class="fas fa-chart-line"></i> Panel Admin
                </a>
                <a href="admin_catalogo.php" class="admin-btn active">
                 <i class="fas fa-tags"></i> Editar Catálogo 
                </a>
              <?php endif; ?>
                <a href="analitica.php" class="history-btn">
                  <i class="fas fa-history"></i> Historial de Compras
                </a>
                <a href="config/logout.php" class="logout-btn">
                  <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
              </div>
            </li>
            <?php endif; ?>
            
          </ul>
        </div>
      </div>
    </nav>
  </header>

  <section class="contracts-hero" id="inicio">
    <div class="banner-container"><img src="patron1.svg" alt="Patrón de fondo" /></div>
    <div class="container" data-aos="fade-up">
      <div class="contracts-hero-content text-center">
        <h1 class="contracts-main-title">Editar <span class="highlight-gradient">Catálogo</span> <i class="fa-solid fa-tags title-icon"></i></h1>
        <p class="contracts-subtitle" style="margin: 0 auto;">Actualiza precios del catálogo sin tocar el código.</p>
      </div>
    </div>
  </section>

  <section class="history-container">
    <div class="container">
      <div class="table-card" data-aos="fade-up" data-aos-delay="100">

        <?php if ($saveOk): ?>
          <div class="alert alert-success"><?php echo htmlspecialchars($saveOk); ?></div>
        <?php endif; ?>
        <?php if ($saveErr): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($saveErr); ?></div>
        <?php endif; ?>

        <div class="d-flex align-items-center justify-content-between mb-3">
          <div>
            <div class="h5 mb-1">Productos</div>
            <div class="muted">Tip: usa formato 99 o 99.00 (MXN).</div>
          </div>
          <a href="admin_analitica.php" class="btn btn-outline-primary rounded-pill">
            <i class="fas fa-arrow-left me-1"></i> Volver al panel
          </a>
        </div>

        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">

          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th style="min-width:260px;">Producto</th>
                  <th>Key</th>
                  <th style="width:180px;">Precio (MXN)</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($catalog as $key => $item):
                  $title = $item['title'] ?? $key;
                  $price = $item['price'] ?? '0.00';
                ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($title); ?></strong></td>
                  <td class="text-muted small"><?php echo htmlspecialchars($key); ?></td>
                  <td>
                    <div class="input-group price-input">
                      <span class="input-group-text">$</span>
                      <input
                        type="text"
                        class="form-control"
                        name="price[<?php echo htmlspecialchars($key); ?>]"
                        value="<?php echo htmlspecialchars($price); ?>"
                        inputmode="decimal"
                        autocomplete="off"
                      />
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="d-flex justify-content-end">
            <button class="btn btn-primary rounded-pill px-4" type="submit">
              <i class="fas fa-save me-1"></i> Guardar cambios
            </button>
          </div>
        </form>

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

  <script>
    window.isLoggedIn = <?php echo isset($_SESSION['usuario_id']) ? 'true' : 'false'; ?>;
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
  <script src="scripts.js"></script>
</body>
</html>