<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/admin_flag.php';

$userId = (int)$_SESSION['usuario_id'];

// Obtener datos del usuario
$stmtUser = $pdo->prepare("SELECT nombre, correo, telefono, ciudad FROM usuarios WHERE id = ?");
$stmtUser->execute([$userId]);
$userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$userData) {
    // Si no existe el usuario por alguna razón
    header("Location: config/logout.php");
    exit;
}

// Obtener cursos guardados
$stmtCourses = $pdo->prepare("
    SELECT c.*, uc.status, uc.progress 
    FROM user_courses uc 
    JOIN courses c ON uc.course_id = c.id 
    WHERE uc.user_id = ? 
    ORDER BY uc.added_at DESC
");
$stmtCourses->execute([$userId]);
$userCourses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);

function e(?string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

function modality_icon(?string $m): string {
  $m = mb_strtolower(trim((string)$m), 'UTF-8');
  if (str_contains($m, 'presencial')) return 'fa-location-dot';
  if (str_contains($m, 'línea') || str_contains($m, 'linea') || str_contains($m, 'online')) return 'fa-globe';
  if (str_contains($m, 'inform')) return 'fa-circle-info';
  return 'fa-book-open';
}

function category_key(?string $cat): string {
  $c = mb_strtolower(trim((string)$cat), 'UTF-8');
  if ($c === '') return 'otros';
  if (str_contains($c, 'ux') || str_contains($c, 'ui')) return 'ux';
  if (str_contains($c, 'anim') || str_contains($c, 'motion')) return 'animacion';
  if (str_contains($c, 'tecno') || str_contains($c, 'inteligencia') || $c === 'ia') return 'tecnologia';
  if (str_contains($c, 'dise')) return 'diseno';
  if (str_contains($c, 'marketing')) return 'marketing';
  if (str_contains($c, 'finan')) return 'finanzas';
  return 'otros';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crece Diseño - Mi Perfil</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tipografías -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- AOS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet" />

    <!-- CSS principal -->
    <link rel="stylesheet" href="assets/css/styles.css">
    <!-- CSS de contratos para estilos de cabecera y listado -->
    <link rel="stylesheet" href="assets/css/contratos.css">

    <style>
        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 18px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid rgba(0,0,0,0.08);
            margin-bottom: 40px;
        }
        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px solid rgba(0,0,0,0.08);
        }
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--bright-blue), var(--dark-purple));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            box-shadow: 0 5px 15px rgba(33, 124, 227, 0.3);
        }
        .profile-title h2 {
            margin: 0;
            color: var(--dark-purple);
            font-family: 'Quicksand', sans-serif;
            font-weight: 700;
        }
        .profile-title p {
            margin: 0;
            color: #666;
            font-size: 1.1rem;
        }
        .profile-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        .detail-label {
            font-size: 0.85rem;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .detail-value {
            font-size: 1.1rem;
            color: var(--dark-blue);
            font-weight: 500;
        }

        /* Course Cards Styles (adapted from cursos.php) */
        .course-card2{
            background:#fff; border-radius:14px; overflow:hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            border:1px solid rgba(0,0,0,0.08); 
            display:flex; flex-direction:column;
            transition: transform .3s ease, box-shadow .3s ease;
            position: relative;
        }
        .course-card2:hover{ transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0,0,0,0.15); border-color: var(--bright-blue); }

        .course-card2__head{ padding:16px 16px 14px; position:relative; }
        .card-head-free{ background: linear-gradient(135deg, rgba(33,124,227,.15), rgba(91,67,147,.15)); }
        .card-head-pay{ background: linear-gradient(135deg, rgba(91,67,147,.15), rgba(33,124,227,.12)); }
        .card-head-fin{ background: linear-gradient(135deg, rgba(25,28,54,.08), rgba(33,124,227,.08)); }

        .course-ico{
            width:40px; height:40px; border-radius:12px;
            display:flex; align-items:center; justify-content:center;
            background: rgba(255,255,255,1);
            border: 1px solid rgba(0,0,0,.08);
            color:#217CE3; flex: 0 0 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .badge-free{ background:#217CE3; }
        .badge-pay{ background:#1A1C36; }
        .badge-fin{ background:#5B4393; }

        .course-card2__body{ padding:16px; display:flex; flex-direction:column; flex:1; }

        .course-controls {
            display: flex;
            gap: 10px;
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid rgba(0,0,0,0.05);
        }
        .status-select {
            flex: 1;
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.9rem;
            color: var(--dark-blue);
            cursor: pointer;
            outline: none;
            transition: border-color 0.2s;
        }
        .status-select:focus {
            border-color: var(--bright-blue);
        }
        .btn-delete-course {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: none;
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-delete-course:hover {
            background: #dc3545;
            color: white;
            transform: scale(1.05);
        }
    </style>
</head>
<body>

  <!-- Fondo base (geometric grid from listado will be on the section) -->
  <div class="background-container"></div>

  <!-- NAVBAR -->
  <header class="shadow-sm">
    <nav class="navbar navbar-expand-lg bg-white-95 fixed-top custom-navbar" id="mainNavbar">
      <div class="container-fluid px-2 px-sm-3 px-lg-4">

        <a class="navbar-brand d-flex align-items-center me-auto brand-left" href="index.php">
          <img src="assets/img/logo.svg" alt="Crece Diseño" class="brand-logo" />
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
            <li class="nav-item"><a class="nav-link" href="foro.php">Foro</a></li>
            <li class="nav-item"><a class="nav-link" href="nosotros.php">Nosotros</a></li>
            <li class="nav-item"><a class="nav-link" href="index.php#contacto">Contacto</a></li>

            <li class="nav-item user-profile-menu">
              <button class="user-toggle" aria-expanded="false">
                <i class="fas fa-user-circle"></i>
                <span>Hola, <?= e($_SESSION['usuario_nombre']) ?></span>
                <i class="fas fa-chevron-down small"></i>
              </button>
              <div class="user-dropdown">
                <div class="user-info">
                  <span class="user-name"><?= e($_SESSION['usuario_nombre']) ?></span>
                  <?php if(isset($_SESSION['usuario_correo'])): ?>
                    <span class="user-email"><?= e($_SESSION['usuario_correo']) ?></span>
                  <?php endif; ?>
                </div>
                <?php if (!empty($isAdmin)): ?>
                  <a href="admin_analitica.php" class="dropdown-item mb-1" style="text-decoration: none; color: var(--dark-blue); font-weight: 600; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 6px; transition: background 0.2s;">
                    <i class="fas fa-chart-line"></i> Panel Admin
                  </a>
                  <a href="admin_catalogo.php" class="admin-btn">
                 <i class="fas fa-tags"></i> Editar Catálogo
                </a>
                <?php endif; ?>
                <a href="mi_perfil.php" class="dropdown-item mb-1" style="text-decoration: none; color: var(--dark-blue); font-weight: 600; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 6px; transition: background 0.2s;">
                  <i class="fas fa-user"></i> Mi Perfil
                </a>
                <a href="analitica.php" class="dropdown-item mb-1" style="text-decoration: none; color: var(--dark-blue); font-weight: 600; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 6px; transition: background 0.2s;">
                  <i class="fas fa-history"></i> Historial de Compras
                </a>
                <a href="config/logout.php" class="logout-btn">
                  <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
              </div>
            </li>
          </ul>
        </div>

      </div>
    </nav>
  </header>

  <!-- HERO -->
  <section class="contracts-hero" id="inicio">
    <div class="banner-container">
      <img src="assets/img/patron1.svg" alt="Patrón de fondo" />
    </div>

    <div class="container">
      <div class="contracts-hero-content" data-aos="fade-up" data-aos-duration="800">
        <h1 class="contracts-main-title">Mi <span class="highlight-gradient">Perfil</span> <i class="fa-solid fa-user title-icon"></i></h1>
        <p class="contracts-subtitle">
          Administra tu información personal y los cursos que has seleccionado.
        </p>
      </div>
    </div>
  </section>

  <!-- LISTADO Y DATOS -->
  <section class="contracts-list" style="min-height: 60vh;">
    <div class="container">
        
        <!-- Datos de Cuenta -->
        <div class="row mb-5" data-aos="fade-up">
            <div class="col-12" style="max-width: 800px; margin: 0 auto;">
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div class="profile-title">
                            <h2><?= e($userData['nombre']) ?></h2>
                            <p><?= e($userData['correo']) ?></p>
                        </div>
                    </div>
                    <div class="profile-details">
                        <div class="detail-item">
                            <span class="detail-label">Teléfono</span>
                            <span class="detail-value"><?= !empty($userData['telefono']) ? e($userData['telefono']) : 'No especificado' ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Ciudad</span>
                            <span class="detail-value"><?= !empty($userData['ciudad']) ? e($userData['ciudad']) : 'No especificada' ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cursos Seleccionados -->
        <div class="row">
            <div class="col-12 text-center mb-4" data-aos="fade-up">
                <h2 style="color: var(--dark-purple); font-family: 'Quicksand', sans-serif; font-weight: 700;">Mis Cursos Seleccionados</h2>
                <div style="width: 60px; height: 3px; background: var(--bright-blue); margin: 10px auto; border-radius: 2px;"></div>
            </div>
        </div>

        <div class="row g-4" id="misCursosGrid">
            <?php if (empty($userCourses)): ?>
                <div class="col-12 text-center" data-aos="fade-up">
                    <div class="p-5" style="background: rgba(255,255,255,0.8); border-radius: 18px; border: 1px dashed #ccc;">
                        <i class="fa-solid fa-book-open mb-3" style="font-size: 3rem; color: #cbd5e1;"></i>
                        <h4 style="color: #64748b;">Aún no has seleccionado ningún curso</h4>
                        <p class="text-muted mb-4">Explora nuestro catálogo y guarda los cursos que te interesen.</p>
                        <a href="cursos.php" class="btn-cta text-white px-4 py-2" style="border-radius: 8px; text-decoration: none; display: inline-block;">Ver Catálogo de Formación</a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach($userCourses as $index => $c): 
                    $title = e($c['title'] ?? '');
                    $category = e($c['category'] ?? '');
                    $icon = modality_icon($c['modality'] ?? '');
                    $tone = ($c['tab'] === 'paga') ? 'pay' : (($c['tab'] === 'financiamiento') ? 'fin' : 'free');
                    $headerClass = ($tone === 'fin') ? 'card-head-fin' : (($tone === 'pay') ? 'card-head-pay' : 'card-head-free');
                    $badgeClass  = ($tone === 'fin') ? 'badge-fin' : (($tone === 'pay') ? 'badge-pay' : 'badge-free');
                    $catKey = category_key($c['category'] ?? '');
                    $cursoStatus = $c['status'] ?? 'pendiente';
                ?>
                <div class="col-12 col-md-6 col-lg-4 course-item" id="course-card-<?= $c['id'] ?>" data-aos="fade-up" data-aos-delay="<?= ($index % 3) * 100 ?>">
                    <div class="course-card2 h-100" data-category="<?= $catKey ?>">
                        <div class="course-card2__head <?= $headerClass ?>">
                            <div class="d-flex align-items-start gap-2">
                                <div class="course-ico"><i class="fa-solid <?= $icon ?>"></i></div>
                                <div class="flex-grow-1">
                                    <h5 class="m-0 fw-bold"><?= $title ?></h5>
                                    <?php if ($category !== ''): ?>
                                    <span class="badge <?= $badgeClass ?> mt-2"><?= $category ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="course-card2__body">
                            <ul class="course-meta" style="margin-bottom:auto;">
                                <?php if (!empty($c['provider'])): ?><li><span>Institución</span><strong><?= e($c['provider']) ?></strong></li><?php endif; ?>
                                <?php if (!empty($c['modality'])): ?><li><span>Modalidad</span><strong><?= e($c['modality']) ?></strong></li><?php endif; ?>
                            </ul>

                            <div class="course-controls">
                                <select class="status-select" onchange="updateCourseStatus(<?= $c['id'] ?>, this.value)">
                                    <option value="pendiente" <?= $cursoStatus === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                    <option value="en curso" <?= $cursoStatus === 'en curso' ? 'selected' : '' ?>>En Curso</option>
                                    <option value="completado" <?= $cursoStatus === 'completado' ? 'selected' : '' ?>>Completado</option>
                                </select>
                                <button class="btn-delete-course" onclick="deleteCourse(<?= $c['id'] ?>)" title="Eliminar de mi lista">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
  </section>

  <!-- Footer -->
  <footer>
    <div class="container">
      <div class="footer-container">
        <div class="footer-col">
          <h3>Crece Diseño</h3>
          <p>Catálogo de vinculación y contratos.</p>
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
          <a href="templates/contratoPRESTACIONDESERVICIOS.html">Prestación de servicios</a>
          <a href="templates/contratoCESIONDEDERECHOS.HTML">Cesión de derechos</a>
        </div>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
  
  <script>
    AOS.init({ duration: 800, once: true });

    // Navbar effect
    (function () {
      const navbar = document.getElementById('mainNavbar');
      if (!navbar) return;
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

    // User profile menu toggle
    document.addEventListener('DOMContentLoaded', () => {
      const userMenu = document.querySelector('.user-profile-menu');
      if (userMenu) {
        const toggleBtn = userMenu.querySelector('.user-toggle');
        toggleBtn.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          const isExpanded = toggleBtn.getAttribute('aria-expanded') === 'true';
          toggleBtn.setAttribute('aria-expanded', !isExpanded);
          userMenu.classList.toggle('active');
        });

        document.addEventListener('click', (e) => {
          if (!userMenu.contains(e.target) && userMenu.classList.contains('active')) {
            userMenu.classList.remove('active');
            toggleBtn.setAttribute('aria-expanded', 'false');
          }
        });
      }
    });

    // API calls for course management
    async function updateCourseStatus(courseId, newStatus) {
        const body = new URLSearchParams();
        body.set('action', 'update_status');
        body.set('course_id', courseId);
        body.set('status', newStatus);

        try {
            const r = await fetch('api/api_user_courses.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body
            });
            const data = await r.json();
            if(!data.ok) {
                alert('Hubo un error al actualizar el estado.');
            }
        } catch(err) {
            console.error(err);
        }
    }

    async function deleteCourse(courseId) {
        if(!confirm('¿Estás seguro de eliminar este curso de tu perfil?')) return;

        const body = new URLSearchParams();
        body.set('action', 'delete');
        body.set('course_id', courseId);

        try {
            const r = await fetch('api/api_user_courses.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body
            });
            const data = await r.json();
            if(data.ok) {
                // Animate out
                const card = document.getElementById(`course-card-${courseId}`);
                card.style.transition = 'all 0.4s ease';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    card.remove();
                    // Check if grid is empty
                    const grid = document.getElementById('misCursosGrid');
                    if(grid.querySelectorAll('.course-item').length === 0) {
                        location.reload(); // Reload to show empty state
                    }
                }, 400);
            } else {
                alert('No se pudo eliminar el curso.');
            }
        } catch(err) {
            console.error(err);
        }
    }
  </script>
</body>
</html>
