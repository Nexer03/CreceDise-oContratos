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

// CSRF token
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_token'];

$saveOk = null;
$saveErr = null;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf, $token)) {
        $saveErr = "Token inválido. Recarga la página e intenta de nuevo.";
    } else {
        if ($_POST['action'] === 'save_course') {
            $course_id = (int)($_POST['course_id'] ?? 0);
            $slug = trim($_POST['slug'] ?? '');
            $title = trim($_POST['title'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $tab = trim($_POST['tab'] ?? '');
            $subtab = trim($_POST['subtab'] ?? '');
            $provider = trim($_POST['provider'] ?? '');
            $modality = trim($_POST['modality'] ?? '');
            $cost_label = trim($_POST['cost_label'] ?? '');
            $url = trim($_POST['url'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if (empty($slug) || empty($title)) {
                $saveErr = "El slug y el título son obligatorios.";
            } else {
                try {
                    // Check if slug exists for a different course
                    $stmtCheck = $pdo->prepare("SELECT id FROM courses WHERE slug = :slug AND id != :id");
                    $stmtCheck->execute([':slug' => $slug, ':id' => $course_id]);
                    if ($stmtCheck->fetch()) {
                        $saveErr = "Ya existe un curso con el identificador (slug) '{$slug}'.";
                    } else {
                        if ($course_id > 0) {
                            // Update existing
                            $stmt = $pdo->prepare("
                                UPDATE courses SET 
                                    slug = :slug, title = :title, category = :category, tab = :tab, 
                                    subtab = :subtab, provider = :provider, modality = :modality, 
                                    cost_label = :cost_label, url = :url, description = :description, 
                                    is_active = :is_active 
                                WHERE id = :id
                            ");
                            $stmt->execute([
                                ':id' => $course_id, ':slug' => $slug, ':title' => $title, ':category' => $category,
                                ':tab' => $tab, ':subtab' => $subtab, ':provider' => $provider, ':modality' => $modality,
                                ':cost_label' => $cost_label, ':url' => $url, ':description' => $description, ':is_active' => $is_active
                            ]);
                            $saveOk = "Curso actualizado correctamente.";
                        } else {
                            // Create new
                            $stmt = $pdo->prepare("
                                INSERT INTO courses (slug, title, category, tab, subtab, provider, modality, cost_label, url, description, is_active) 
                                VALUES (:slug, :title, :category, :tab, :subtab, :provider, :modality, :cost_label, :url, :description, :is_active)
                            ");
                            $stmt->execute([
                                ':slug' => $slug, ':title' => $title, ':category' => $category,
                                ':tab' => $tab, ':subtab' => $subtab, ':provider' => $provider, ':modality' => $modality,
                                ':cost_label' => $cost_label, ':url' => $url, ':description' => $description, ':is_active' => $is_active
                            ]);
                            $saveOk = "Curso creado correctamente.";
                        }
                    }
                } catch (PDOException $e) {
                    $saveErr = "Error de base de datos: " . $e->getMessage();
                }
            }
        } elseif ($_POST['action'] === 'delete_course') {
            $course_id = (int)($_POST['delete_id'] ?? 0);
            if ($course_id > 0) {
                try {
                    // Also consider deleting referencing records from user_courses or relying on ON DELETE CASCADE
                    // For safety, let's just delete the course (assuming ON DELETE CASCADE on user_courses is set, if not, might fail)
                    $stmt = $pdo->prepare("DELETE FROM courses WHERE id = :id");
                    $stmt->execute([':id' => $course_id]);
                    $saveOk = "Curso eliminado correctamente.";
                } catch (PDOException $e) {
                    // If foreign key constraint fails, suggest soft delete
                    if ($e->getCode() == 23000) {
                        $saveErr = "No se puede eliminar porque hay usuarios con este curso seleccionado. Desactívalo mejor desmarcando 'Activo'.";
                    } else {
                        $saveErr = "Error al eliminar: " . $e->getMessage();
                    }
                }
            }
        }
    }
}

// Fetch Courses
$courses = [];
try {
    $stmt = $pdo->query("SELECT * FROM courses ORDER BY id DESC");
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $saveErr = "Error cargando cursos: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Crece Diseño - Admin Cursos</title>

  <!-- Tipografías -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- AOS -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet" />
  <!-- CSS principal -->
  <link rel="stylesheet" href="assets/css/styles.css" />
  <link rel="stylesheet" href="assets/css/contratos.css" />

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
    .muted { color:#6c757d; font-size:.9rem; }
    .contracts-hero {
      margin-top: 0 !important;
    }
    :root { --nav-offset: 96px; } /* fallback */
    .status-badge { font-size: 0.8rem; padding: 4px 8px; border-radius: 6px; }
  </style>
</head>

<body>
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
                <a href="admin_analitica.php" class="dropdown-item mb-1" style="text-decoration: none; color: var(--dark-blue); font-weight: 600; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 6px; transition: background 0.2s;">
                  <i class="fas fa-chart-line"></i> Panel Admin
                </a>
                <a href="admin_catalogo.php" class="admin-btn">
                 <i class="fas fa-tags"></i> Editar Catálogo 
                </a>
                <a href="admin_cursos.php" class="admin-btn">
                 <i class="fas fa-book-open"></i> Editar Cursos
                </a>
                <?php endif; ?>
                <a href="mi_perfil.php" class="dropdown-item mb-1" style="text-decoration: none; color: var(--dark-blue); font-weight: 600; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 6px; transition: background 0.2s;">
                  <i class="fas fa-user"></i> Mi Perfil
                </a>
                <a href="analitica.php" class="dropdown-item mb-1" style="text-decoration: none; color: var(--dark-blue); font-weight: 600; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 6px; transition: background 0.2s;">
                  <i class="fas fa-history"></i> Historial de Compras
                </a>
                <a href="config/logout.php" class="logout-btn">
                  <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
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
    <div class="banner-container"><img src="assets/img/patron1.svg" alt="Patrón de fondo" /></div>
    <div class="container" data-aos="fade-up">
      <div class="contracts-hero-content text-center">
        <h1 class="contracts-main-title">Editar <span class="highlight-gradient">Cursos</span> <i class="fa-solid fa-graduation-cap title-icon"></i></h1>
        <p class="contracts-subtitle" style="margin: 0 auto;">Gestión del catálogo de formación y enlaces educativos.</p>
      </div>
    </div>
  </section>

  <section class="history-container">
    <div class="container">
      <div class="table-card" data-aos="fade-up" data-aos-delay="100">

        <?php if ($saveOk): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($saveOk); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>
        <?php if ($saveErr): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($saveErr); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-3">
          <div>
            <div class="h5 mb-1">Catálogo de Cursos</div>
            <div class="muted">Listado de todos los cursos disponibles en la plataforma.</div>
          </div>
          <div class="d-flex gap-2">
              <button class="btn btn-primary rounded-pill btn-add-course" data-bs-toggle="modal" data-bs-target="#courseModal">
                <i class="fas fa-plus me-1"></i> Agregar Nuevo
              </button>
              <a href="admin_analitica.php" class="btn btn-outline-secondary rounded-pill">
                <i class="fas fa-arrow-left me-1"></i> Panel Admin
              </a>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th style="min-width:300px;">Título & Descripción</th>
                <th>Proveedor</th>
                <th>Modalidad / Costo</th>
                <th>Ubicación (Tab)</th>
                <th>Estado</th>
                <th class="text-end" style="width:120px;">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($courses as $c): ?>
              <tr>
                <td>
                    <div class="fw-bold text-dark mb-1">
                        <?php echo htmlspecialchars($c['title']); ?>
                    </div>
                    <div class="small mb-1" style="color: #4a5568; font-weight: 500;"><code><?php echo htmlspecialchars($c['slug']); ?></code> | Cat: <em><?php echo htmlspecialchars($c['category']); ?></em></div>
                    <div class="small text-truncate" style="color: #4a5568; max-width:300px; line-height: 1.4;"><?php echo htmlspecialchars($c['description']); ?></div>
                </td>
                <td>
                    <div class="fw-semibold"><?php echo htmlspecialchars($c['provider']); ?></div>
                </td>
                <td>
                    <div class="mb-1"><span class="badge bg-light text-dark border"><i class="fas fa-chalkboard-teacher me-1"></i> <?php echo htmlspecialchars($c['modality']); ?></span></div>
                    <div><span class="badge bg-light text-success border"><i class="fas fa-tag me-1"></i> <?php echo htmlspecialchars($c['cost_label']); ?></span></div>
                </td>
                <td>
                    <?php 
                        $tabStr = $c['tab'] === 'paga' ? 'De Paga' : ($c['tab'] === 'gratuitos' ? 'Gratuitos' : 'Financiamiento');
                        $subtabStr = $c['subtab'] === 'digital' ? 'En Línea' : 'Presencial';
                    ?>
                    <div><strong><?php echo htmlspecialchars($tabStr); ?></strong></div>
                    <div class="small text-muted"><?php echo htmlspecialchars($subtabStr); ?></div>
                </td>
                <td>
                    <?php if($c['is_active']): ?>
                        <span class="badge bg-success status-badge">Activo</span>
                    <?php else: ?>
                        <span class="badge bg-secondary status-badge">Inactivo</span>
                    <?php endif; ?>
                </td>
                <td class="text-end">
                    <div class="d-flex justify-content-end gap-1">
                        <button class="btn btn-sm btn-outline-warning rounded-circle edit-course-btn" style="width: 32px; height: 32px; padding: 0;"
                            data-course='<?php echo htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8'); ?>'
                            data-bs-toggle="modal" data-bs-target="#courseModal" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-course-btn" 
                            style="width: 32px; height: 32px; padding: 0;" title="Eliminar"
                            data-id="<?php echo (int)$c['id']; ?>"
                            data-title="<?php echo htmlspecialchars($c['title']); ?>"
                            data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <?php if(!empty($c['url'])): ?>
                        <div class="mt-2 text-end">
                            <a href="<?php echo htmlspecialchars($c['url']); ?>" target="_blank" class="small text-decoration-none" title="Ver Link Original"><i class="fas fa-external-link-alt"></i> Ver Link</a>
                        </div>
                    <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
              
              <?php if (empty($courses)): ?>
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">Aún no hay cursos registrados en el sistema.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </section>

  <!-- Course Modal -->
  <div class="modal fade" id="courseModal" tabindex="-1" aria-labelledby="courseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content" style="border-radius: 20px; border:none; box-shadow: 0 15px 50px rgba(0,0,0,0.1);">
        <div class="modal-header border-bottom-0 pb-0">
          <h5 class="modal-title section-title px-2 pt-2" id="courseModalLabel">Modificar Curso</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
          <input type="hidden" name="action" value="save_course">
          <input type="hidden" name="course_id" id="course_id" value="">
          
          <div class="modal-body px-4 py-3">
            <div class="row g-3">
                
                <div class="col-md-12">
                  <label for="title" class="form-label fw-bold small mb-1">Título del Curso <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="title" name="title" required placeholder="Ej. Curso Completo de HTML y CSS" style="border-radius: 12px;">
                </div>

                <div class="col-md-6">
                  <label for="slug" class="form-label fw-bold small mb-1">Identificador (Slug) <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="slug" name="slug" placeholder="curso-html-css" required pattern="[a-z0-9\-]+" style="border-radius: 12px;">
                  <div class="form-text small">Solo letras (sin acentos/ñ), números y guiones medios.</div>
                </div>
                
                <div class="col-md-6">
                  <label for="provider" class="form-label fw-bold small mb-1">Institución / Proveedor</label>
                  <input type="text" class="form-control" id="provider" name="provider" placeholder="Domestika, Google, etc." style="border-radius: 12px;">
                </div>

                <div class="col-md-4">
                  <label for="tab" class="form-label fw-bold small mb-1">Pestaña Principal</label>
                  <select class="form-select" id="tab" name="tab" style="border-radius: 12px;">
                      <option value="gratuitos">Gratuitos</option>
                      <option value="paga">De Paga</option>
                      <option value="financiamiento">Financiamiento</option>
                  </select>
                </div>

                <div class="col-md-4">
                  <label for="subtab" class="form-label fw-bold small mb-1">Sub Pestaña</label>
                  <select class="form-select" id="subtab" name="subtab" style="border-radius: 12px;">
                      <option value="digital">Digital / En Línea</option>
                      <option value="presencial">Presencial</option>
                  </select>
                </div>
                
                <div class="col-md-4">
                  <label for="category" class="form-label fw-bold small mb-1">Categoría General</label>
                  <input type="text" class="form-control" id="category" name="category" placeholder="Diseño, Tecnología..." style="border-radius: 12px;">
                </div>

                <div class="col-md-6">
                  <label for="modality" class="form-label fw-bold small mb-1">Modalidad (Texto)</label>
                  <input type="text" class="form-control" id="modality" name="modality" placeholder="En línea, Autogestivo..." style="border-radius: 12px;">
                </div>

                <div class="col-md-6">
                  <label for="cost_label" class="form-label fw-bold small mb-1">Etiqueta Costo</label>
                  <input type="text" class="form-control" id="cost_label" name="cost_label" placeholder="Gratuito, De Paga, Con Beca..." style="border-radius: 12px;">
                </div>

                <div class="col-12">
                  <label for="url" class="form-label fw-bold small mb-1">URL Enlace al Curso</label>
                  <input type="url" class="form-control" id="url" name="url" placeholder="https://..." style="border-radius: 12px;">
                </div>

                <div class="col-12">
                  <label for="description" class="form-label fw-bold small mb-1">Descripción Breve</label>
                  <textarea class="form-control" id="description" name="description" rows="3" placeholder="Resumen corto sobre de qué trata el curso." style="border-radius: 12px;"></textarea>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" checked>
                        <label class="form-check-label fw-bold small" for="is_active" style="cursor: pointer;">Mostrar curso en el catálogo (Activo)</label>
                    </div>
                </div>

            </div>
          </div>
          <div class="modal-footer border-top-0 px-4 pb-4">
            <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm"><i class="fas fa-save me-1"></i> Guardar Curso</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
      <div class="modal-content" style="border-radius: 16px; border:none; box-shadow: 0 10px 40px rgba(0,0,0,0.15);">
        <div class="modal-header border-bottom-0 pb-0">
          <h6 class="modal-title w-100 text-center" id="deleteModalLabel" style="font-weight: 700; color: #dc3545;">
            <i class="fas fa-exclamation-triangle me-1"></i> Confirmar
          </h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position:absolute; right:15px; top:15px;"></button>
        </div>
        <div class="modal-body text-center py-3">
          <div class="mb-2" style="font-size: 2rem; color: #dc3545;"><i class="fas fa-trash-alt"></i></div>
          <p class="mb-1 text-dark fw-semibold" style="font-size: 0.95rem;">¿Deseas eliminar este curso?</p>
          <p class="fw-bold mb-2" id="deleteCourseName" style="color: var(--dark-blue); font-size: 1rem;"></p>
          <p class="small mb-0" style="color: #4a5568; line-height: 1.3;">Esta acción es irreversible y lo borrará del sistema.</p>
        </div>
        <div class="modal-footer border-top-0 justify-content-center pt-0 pb-3">
          <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-bold" data-bs-dismiss="modal">Cancelar</button>
          <form method="POST" id="deleteForm" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
            <input type="hidden" name="action" value="delete_course">
            <input type="hidden" name="delete_id" id="delete_id_input" value="">
            <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 shadow-sm fw-bold">Sí, eliminar</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer>
        <div class="container">
            <div class="footer-container">
                <div class="footer-col">
                    <h3>Crece Diseño</h3>
                    <p>Catálogo de Formación para diseñadores gráficos en Puerto Vallarta.</p>
                    <div class="social-links">
                        <a href="https://instagram.com/crecediseño" class="social-link" target="_blank"><i class="fab fa-instagram"></i></a>
                        <a href="https://tiktok.com/@crecediseño" class="social-link" target="_blank"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>
                <div class="footer-col" data-aos="fade-up" data-aos-delay="100">
                    <h3>Enlaces Rápidos</h3>
                    <a href="index.php">Inicio</a>
                    <a href="cursos.php">Cursos</a>
                    <a href="nosotros.php">Nosotros</a>
                    <a href="foro.php">Foro</a>
                </div>
                <div class="footer-col">
                    <h3>Cursos</h3>
                    <a href="cursos.html#gratuitos">Cursos Gratuitos</a>
                    <a href="cursos.html#paga">Cursos de Paga</a>
                    <a href="#">Certificaciones</a>
                    <a href="#">Talleres</a>
                </div>
                <div class="footer-col">
                    <h3>Contacto</h3>
                    <p>contacto@crecediseño.com</p>
                    <p>+52 322 123 4567</p>
                    <p>Puerto Vallarta, Jalisco</p>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; 2025 Crece Diseño. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
  <script src="assets/js/scripts.js"></script>
  <script>
    AOS.init({ once: true, offset: 50 });

    const nav = document.querySelector('.custom-navbar');
    const setOffset = () => {
      const h = nav ? nav.offsetHeight : 80;
      document.documentElement.style.setProperty('--nav-offset', h + 'px');
    };
    setOffset();
    window.addEventListener('resize', setOffset);

    document.addEventListener('DOMContentLoaded', () => {
        const modalTitle = document.getElementById('courseModalLabel');
        const courseIdInp = document.getElementById('course_id');
        
        const slugInp = document.getElementById('slug');
        const titleInp = document.getElementById('title');
        const providerInp = document.getElementById('provider');
        const categoryInp = document.getElementById('category');
        const tabInp = document.getElementById('tab');
        const subtabInp = document.getElementById('subtab');
        const modalityInp = document.getElementById('modality');
        const costLabelInp = document.getElementById('cost_label');
        const urlInp = document.getElementById('url');
        const descInp = document.getElementById('description');
        const isActiveInp = document.getElementById('is_active');

        // Reset form for adding a new course
        document.querySelector('.btn-add-course').addEventListener('click', () => {
            modalTitle.innerText = "Agregar Nuevo Curso";
            courseIdInp.value = "";
            slugInp.value = "";
            titleInp.value = "";
            providerInp.value = "";
            categoryInp.value = "Diseño";
            tabInp.value = "gratuitos";
            subtabInp.value = "digital";
            modalityInp.value = "En línea";
            costLabelInp.value = "Gratuito";
            urlInp.value = "";
            descInp.value = "";
            isActiveInp.checked = true;
        });

        // Populate form when editing
        document.querySelectorAll('.edit-course-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                modalTitle.innerText = "Modificar Curso";
                const c = JSON.parse(btn.getAttribute('data-course'));
                
                courseIdInp.value = c.id;
                slugInp.value = c.slug;
                titleInp.value = c.title;
                providerInp.value = c.provider;
                categoryInp.value = c.category;
                tabInp.value = c.tab;
                subtabInp.value = c.subtab;
                modalityInp.value = c.modality;
                costLabelInp.value = c.cost_label;
                urlInp.value = c.url || "";
                descInp.value = c.description || "";
                isActiveInp.checked = parseInt(c.is_active) === 1;
            });
        });

        // Handle delete modal population
        document.querySelectorAll('.delete-course-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('delete_id_input').value = btn.getAttribute('data-id');
                document.getElementById('deleteCourseName').innerText = btn.getAttribute('data-title');
            });
        });
        
        // Auto-generate slug from title (basic)
        titleInp.addEventListener('input', () => {
             if (courseIdInp.value === "") {
                 let draft = titleInp.value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                 draft = draft.replace(/[^a-z0-9\s]/g, '').trim().replace(/\s+/g, '-');
                 slugInp.value = draft;
             }
        });
    });
  </script>
</body>
</html>
