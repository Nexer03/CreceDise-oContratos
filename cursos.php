<?php
session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/admin_flag.php';

$nombre_usuario = $_SESSION['usuario_nombre'] ?? 'Usuario';

// Obtener cursos activos (una sola vez)
$stmt = $pdo->query("SELECT id, slug, title, category, tab, subtab, provider, modality, cost_label, url, description FROM courses WHERE is_active = 1 ORDER BY tab, subtab, created_at DESC");
$courses = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Agrupar por tipo y subtipos
$gratuitos_digital = [];
$gratuitos_presencial = [];

$paga_digital = [];
$paga_presencial = [];

$financiamiento = [];

foreach ($courses as $c) {
  $tab = $c['tab'] ?? '';
  $sub = $c['subtab'] ?? '';

  if ($tab === 'gratuitos') {
    if ($sub === 'presencial-gratuito') $gratuitos_presencial[] = $c;
    else $gratuitos_digital[] = $c; // default digital-gratuito
  } elseif ($tab === 'paga') {
    if ($sub === 'presencial-paga') $paga_presencial[] = $c;
    else $paga_digital[] = $c; // default digital-paga
  } elseif ($tab === 'financiamiento') {
    $financiamiento[] = $c;
  }
}

function e(?string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
function trunc(?string $v, int $n = 170): string {
  $v = trim((string)$v);
  if ($v === '') return '';
  if (mb_strlen($v, 'UTF-8') <= $n) return $v;
  return rtrim(mb_substr($v, 0, $n, 'UTF-8')) . '…';
}

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

function render_course_card(array $c, string $tone = 'free'): void {
  $title = e($c['title'] ?? '');
  $category = e($c['category'] ?? '');
  $provider = e($c['provider'] ?? '');
  $modality = e($c['modality'] ?? '');
  $cost = e($c['cost_label'] ?? '');
  $url = trim((string)($c['url'] ?? ''));
  $desc = e(trunc($c['description'] ?? ''));

  $icon = modality_icon($c['modality'] ?? '');

  $headerClass = ($tone === 'fin') ? 'card-head-fin' : (($tone === 'pay') ? 'card-head-pay' : 'card-head-free');
  $badgeClass  = ($tone === 'fin') ? 'badge-fin' : (($tone === 'pay') ? 'badge-pay' : 'badge-free');

  // Checkbox: solo UI (sin guardar)
  $dataId = (int)($c['id'] ?? 0);

  // Para filtros (solo UI)
  $catKey = category_key($c['category'] ?? '');

  echo "\n<div class=\"col-12 col-md-6 col-lg-4 course-item\">\n";
  echo "  <div class=\"course-card2 h-100\" data-category=\"{$catKey}\">\n";
  echo "    <div class=\"course-card2__head {$headerClass}\">\n";
  echo "      <label class=\"course-check2\" title=\"Guardar (más tarde)\">\n";
  echo "        <input type=\"checkbox\" class=\"course-checkbox\" data-course-id=\"{$dataId}\">\n";
  echo "        <span class=\"check-ui\"></span>\n";
  echo "      </label>\n";
  echo "      <div class=\"d-flex align-items-start gap-2\">\n";
  echo "        <div class=\"course-ico\"><i class=\"fa-solid {$icon}\"></i></div>\n";
  echo "        <div class=\"flex-grow-1\">\n";
  echo "          <h5 class=\"m-0 fw-bold\">{$title}</h5>\n";
  if ($category !== '') {
    echo "          <span class=\"badge {$badgeClass} mt-2\">{$category}</span>\n";
  }
  echo "        </div>\n";
  echo "      </div>\n";
  echo "    </div>\n";

  echo "    <div class=\"course-card2__body\">\n";
  if ($desc !== '') {
    echo "      <p class=\"mb-3\" style=\"color: #4b5563; font-size: 0.95rem; font-weight: 500; line-height: 1.5;\">{$desc}</p>\n";
  }

  echo "      <ul class=\"course-meta\">\n";
  if ($provider !== '') echo "        <li><span>Institución</span><strong>{$provider}</strong></li>\n";
  if ($modality !== '') echo "        <li><span>Modalidad</span><strong>{$modality}</strong></li>\n";
  if ($cost !== '') echo "        <li><span>Costo</span><strong>{$cost}</strong></li>\n";
  echo "      </ul>\n";

  echo "      <div class=\"mt-auto pt-2\">\n";
  if ($url !== '') {
    $safeUrl = e($url);
    echo "        <a href=\"{$safeUrl}\" target=\"_blank\" rel=\"noopener\" class=\"btn btn-primary w-100\">Ver curso</a>\n";
  } else {
    echo "        <button class=\"btn btn-outline-secondary w-100\" disabled>Sin enlace</button>\n";
  }
  echo "      </div>\n";

  echo "    </div>\n";
  echo "  </div>\n";
  echo "</div>\n";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Crece Diseño - Cursos</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Tipografías -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- AOS (animaciones) -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet" />

  <!-- CSS principal -->
  <link rel="stylesheet" href="assets/css/styles.css">
  <!-- CSS de contratos para reutilizar estilos -->
  <link rel="stylesheet" href="assets/css/contratos.css">

  <style>
    body{ font-family: 'Montserrat', sans-serif; }

    /* Guía rápida (simple) */
    .quick-simple{ margin: 2.5rem 0; padding: 2rem 1.5rem; background: #f7f8fb; border: 1px solid #e9ecf3; border-radius: 14px; }
    .quick-simple__head{ text-align: center; margin-bottom: 1.25rem; }
    .quick-simple__title{ font-family: 'Quicksand', sans-serif; font-size: 1.6rem; margin: 0 0 .25rem 0; }
    .quick-simple__subtitle{ margin: 0; color: #555; }
    .quick-simple__grid{ display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-top: 1.25rem; }

    .qcard{ 
      background: #fff; border-radius: 14px; padding: 16px; 
      box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
      border: 1px solid rgba(0,0,0,0.08); 
      display: flex; flex-direction: column; 
      transition: transform .3s ease, box-shadow .3s ease; 
      position: relative;
    }
    .qcard:hover{ transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0,0,0,0.15); border-color: var(--bright-blue); }
    
    .qcard__top{ display:flex; align-items:flex-start; justify-content:flex-start; gap:12px; margin-bottom: 8px; }
    .qcard__label{ display:flex; align-items:center; gap:12px; font-weight: 700; color:#1A1C36; }
    .qcard__label i{ 
      width: 40px; height: 40px; border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      background: rgba(255,255,255,1);
      border: 1px solid rgba(0,0,0,.08);
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      font-size: 1.1rem;
    }
    .qcard__desc{ margin: 0 0 16px 0; color:#667085; font-size:.9rem; line-height: 1.4; }
    .qcard__actions{ display:flex; flex-direction:column; gap:10px; margin-top: auto; padding-top: 15px; border-top: 1px solid rgba(0,0,0,0.05); }

    .qbtn{ display:block; width:100%; padding: 8px 12px; border-radius: 8px; text-decoration:none; font-family: 'Montserrat', sans-serif; font-size: 0.9rem; text-align:center; border: 1px solid transparent; font-weight: 600; transition: all 0.2s; }
    .qbtn--primary{ background: #217CE3; color:#fff; }
    .qbtn--primary:hover{ background: #185ba5; color:#fff; transform: translateY(-2px); }
    .qbtn--ghost{ background: #fff; color:#217CE3; border-color: #ddd; }
    .qbtn--ghost:hover{ background: #f8f9fb; border-color: #217CE3; transform: translateY(-2px); }

    .qcheck{ display:flex; align-items:center; cursor:pointer; }
    .qcheck input{ display:none; }
    .qcheck__ui{ width:22px; height:22px; border-radius: 6px; border: 2px solid #cbd5e1; background:#fff; position:relative; }
    .qcheck input:checked + .qcheck__ui{ border-color:#217CE3; background:#217CE3; }
    .qcheck input:checked + .qcheck__ui::after{ content:"✓"; position:absolute; top:50%; left:50%; transform:translate(-50%,-55%); color:#fff; font-weight:900; }

    @media (max-width: 480px){ .quick-simple{ padding: 1.5rem 1rem; } }

    /* Cards */
    .course-card2{
      background:#fff; border-radius:14px; overflow:hidden;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1); /* Sombra mejorada para contraste fondo blanco */
      border:1px solid rgba(0,0,0,0.08); /* Borde más visible */
      display:flex; flex-direction:column;
      transition: transform .3s ease, box-shadow .3s ease;
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

    .course-meta{ list-style:none; padding:0; margin:0 0 12px; display:grid; gap:10px; }
    .course-meta li{ display:flex; justify-content:space-between; gap:10px; padding:10px 12px; border-radius:12px; background:#f8f9fb; border:1px solid rgba(0,0,0,.06); }
    .course-meta span{ color:#667085; font-size:.85rem; }
    .course-meta strong{ color:#1A1C36; font-weight:700; font-size:.9rem; text-align:right; }

    .course-check2{ position:absolute; top:12px; right:12px; cursor:pointer; z-index:5; }
    .course-check2 input{ display:none; }
    .course-check2 .check-ui{
      width:22px; height:22px; border-radius:7px;
      background: rgba(255,255,255,.9);
      border: 2px solid rgba(0,0,0,.1);
      display:inline-block; position:relative;
      box-shadow: 0 2px 8px rgba(0,0,0,.1);
      transition: all 0.2s ease;
    }
    .course-check2:hover .check-ui { background: #fff; border-color: #217CE3; }
    .course-check2 input:checked + .check-ui{ background:#fff; border-color:#217CE3; }
    .course-check2 input:checked + .check-ui::after{
      content:"✓"; position:absolute; top:50%; left:50%;
      transform:translate(-50%,-55%);
      color:#217CE3; font-weight:900; font-size:14px;
    }

    /* Tabs/Chips Styles for Cursos (match contratos chips) */
    .section-pill{ 
      border-radius: 999px !important; 
      padding: 8px 18px !important; 
      font-weight: 700 !important;
      font-family: 'Quicksand', sans-serif !important;
      border: 1px solid rgba(0,0,0,0.15) !important;
      background: rgba(255,255,255,0.8) !important;
      color: var(--dark-blue) !important;
      transition: all 0.3s ease !important;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05) !important;
    }
    .section-pill:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(0,0,0,0.1);
      border-color: rgba(0,0,0,0.3) !important;
    }
    .section-pill.active {
      background: linear-gradient(135deg, #fbc2eb 0%, #a6c1ee 100%) !important;
      color: var(--dark-purple) !important;
      border-color: transparent !important;
      box-shadow: 0 8px 20px rgba(251,194,235,0.3) !important;
      transform: translateY(-1px);
    }
    
    .subpill .nav-link{ 
      border-radius: 999px !important; 
      font-weight: 700 !important;
      padding: 6px 14px !important;
      font-family: 'Quicksand', sans-serif !important;
      border: 1px solid rgba(0,0,0,0.1) !important;
      background: #fff !important;
      color: #555 !important;
      transition: all 0.3s ease !important;
    }
    .subpill .nav-link:hover {
      background: rgba(33,124,227,0.05) !important;
      color: var(--bright-blue) !important;
      border-color: rgba(33,124,227,0.3) !important;
    }
    .subpill .nav-link.active {
      background: var(--bright-blue) !important;
      color: #fff !important;
      border-color: var(--bright-blue) !important;
      box-shadow: 0 4px 10px rgba(33,124,227,0.3) !important;
    }

    /* Category Filter Buttons in Paga */
    .filter-chip {
      border-radius: 999px !important; 
      font-weight: 700 !important;
      padding: 6px 16px !important;
      font-size: 0.9rem !important;
      font-family: 'Quicksand', sans-serif !important;
      border: 1px solid rgba(0,0,0,0.1) !important;
      background: #fff !important;
      color: #555 !important;
      transition: all 0.3s ease !important;
      cursor: pointer;
    }
    .filter-chip:hover {
      background: rgba(33,124,227,0.05) !important;
      color: var(--bright-blue) !important;
      border-color: rgba(33,124,227,0.3) !important;
      transform: translateY(-1px);
    }
    .filter-chip.active {
      background: var(--bright-blue) !important;
      color: #fff !important;
      border-color: var(--bright-blue) !important;
      box-shadow: 0 4px 10px rgba(33,124,227,0.3) !important;
    }

    @media (max-width: 576px){
      .course-meta li{ flex-direction:column; align-items:flex-start; padding: 6px 10px; }
      .course-meta strong{ text-align:left; }
      
      .qcard { padding: 12px; }
      .qcard__label i { width: 32px; height: 32px; font-size: 0.9rem; }
      
      .course-card2__head { padding: 12px 12px 10px; }
      .course-card2__body { padding: 12px; }
      .course-ico { width: 32px; height: 32px; flex: 0 0 32px; font-size: 0.9rem; }
      
      .filter-chip, .section-pill { padding: 6px 12px !important; font-size: 0.85rem !important; }
    }
  </style>
</head>
<body>

<div class="background-container"></div>

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
            <li class="nav-item"><a class="nav-link active" href="cursos.php">Cursos</a></li>
            <li class="nav-item"><a class="nav-link" href="contratos.php">Contratos</a></li>
            <li class="nav-item"><a class="nav-link" href="foro.php">Foro</a></li>
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
            <?php else: ?>
            <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
              <button class="btn btn-outline-primary px-4" style="border-radius: 25px; font-weight: 600; border-width: 2px;" onclick="showRegisterModal(); toggleForms('login');">
                <i class="fas fa-sign-in-alt me-1"></i> Iniciar Sesión
              </button>
            </li>
            <?php endif; ?>
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
        <h1 class="contracts-main-title">Catálogo de <span class="highlight-gradient">Formación</span> <i class="fa-solid fa-graduation-cap title-icon"></i></h1>
        <p class="contracts-subtitle">
          Cursos gratuitos (digitales y presenciales), cursos de paga y recursos de financiamiento.
        </p>
      </div>
    </div>
  </section>

  <!-- GUÍA RÁPIDA -->
  <section class="contracts-list" style="padding-top: 3rem; padding-bottom: 5rem;">
    <div class="container">
      <div class="row mb-5 text-center">
        <div class="col-12" data-aos="fade-up">
          <h2 style="color: var(--dark-purple); font-family: 'Quicksand', sans-serif; font-weight: 700;">Guía Rápida por Necesidad</h2>
          <div style="width: 60px; height: 3px; background: var(--bright-blue); margin: 10px auto; border-radius: 2px;"></div>
          <p class="mt-3" style="color: #4b5563; font-size: 1.1rem; font-weight: 500;">Encuentra el curso perfecto según tu prioridad inmediata</p>
        </div>
      </div>
      
      <div class="row g-4 justify-content-center" style="margin-bottom: 3rem;">
        
        <!-- Tarjeta 1 -->
        <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="0">
          <div class="qcard h-100 d-flex flex-column">
            <div class="qcard__top">
              <div class="qcard__label"><i class="fas fa-building" style="background: rgba(91,67,147,0.1); color: var(--dark-purple);"></i> Formalizar tu Negocio</div>
            </div>
            <p class="qcard__desc flex-grow-1">El 88.9% de diseñadores tiene debilidades en gestión financiera</p>
            <div class="qcard__actions mt-auto">
              <button class="qbtn qbtn--primary w-100" style="background: var(--dark-purple);" onclick="searchCourse('CONDUSEF')">CONDUSEF - Haz de tu idea un negocio</button>
              <button class="qbtn qbtn--ghost w-100" style="color: var(--dark-purple); border-color: rgba(91,67,147,0.3);" onclick="searchCourse('Educación')">Diplomado Educación Financiera</button>
            </div>
          </div>
        </div>

        <!-- Tarjeta 2 -->
        <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="50">
          <div class="qcard h-100 d-flex flex-column">
            <div class="qcard__top">
              <div class="qcard__label"><i class="fas fa-users-viewfinder"></i> Conseguir Clientes</div>
            </div>
            <p class="qcard__desc flex-grow-1">El 66.7% necesita mejorar posicionamiento digital</p>
            <div class="qcard__actions mt-auto">
              <button class="qbtn qbtn--primary w-100" onclick="searchCourse('Marketing Digital')">Marketing Digital</button>
              <button class="qbtn qbtn--primary w-100" style="background: var(--dark-purple);" onclick="searchCourse('Contenidos para Promocionar')">Contenidos para Promocionar</button>
              <button class="qbtn qbtn--ghost w-100" onclick="searchCourse('Certificado Marketing Digital')">Certificado Marketing Digital (Beca)</button>
            </div>
          </div>
        </div>

        <!-- Tarjeta 3 -->
        <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
          <div class="qcard h-100 d-flex flex-column">
            <div class="qcard__top">
              <div class="qcard__label"><i class="fas fa-tools" style="background: rgba(40,167,69,0.1); color: #28a745;"></i> Mejorar Habilidades Técnicas</div>
            </div>
            <p class="qcard__desc flex-grow-1">Domina las herramientas profesionales de diseño</p>
            <div class="qcard__actions mt-auto">
              <button class="qbtn qbtn--primary w-100" style="background: var(--dark-purple);" onclick="searchCourse('Diseño Gráfico Digital')">Diseño Gráfico Digital (Gratis)</button>
              <button class="qbtn qbtn--primary w-100" style="background: var(--dark-purple);" onclick="searchCourse('Canva')">Curso de Canva (Gratis)</button>
              <button class="qbtn qbtn--ghost w-100" onclick="searchCourse('Adobe Creative Suite')">Domestika - Adobe Creative Suite</button>
              <button class="qbtn qbtn--ghost w-100" onclick="searchCourse('IDEFT')">IDEFT Puerto Vallarta - Presencial</button>
            </div>
          </div>
        </div>

        <!-- Tarjeta 4 -->
        <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="150">
          <div class="qcard h-100 d-flex flex-column">
            <div class="qcard__top">
              <div class="qcard__label"><i class="fas fa-mobile-screen" style="background: rgba(220,53,69,0.1); color: #dc3545;"></i> Especializarte en UX/UI</div>
            </div>
            <p class="qcard__desc flex-grow-1">Salarios 40% más altos, trabajo remoto internacional</p>
            <div class="qcard__actions mt-auto">
              <button class="qbtn qbtn--primary w-100" style="background: var(--dark-purple);" onclick="searchCourse('Diseño UX')">Google Certificado - Diseño UX (Beca)</button>
              <button class="qbtn qbtn--ghost w-100" onclick="searchCourse('Ruta Diseño UX')">Platzi - Ruta Diseño UX (Completo)</button>
            </div>
          </div>
        </div>

        <!-- Tarjeta 5 -->
        <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200">
          <div class="qcard h-100 d-flex flex-column">
            <div class="qcard__top">
              <div class="qcard__label"><i class="fas fa-robot" style="background: rgba(91,67,147,0.1); color: var(--dark-purple);"></i> Integrar IA en tu Trabajo</div>
            </div>
            <p class="qcard__desc flex-grow-1">Aumenta tu productividad 300% con herramientas de IA</p>
            <div class="qcard__actions mt-auto">
              <button class="qbtn qbtn--primary w-100" onclick="searchCourse('Gemini')">Domina la IA con Gemini</button>
              <button class="qbtn qbtn--primary w-100" style="background: var(--dark-purple);" onclick="searchCourse('Prompting')">Fundamentos de Prompting</button>
            </div>
          </div>
        </div>

        <!-- Tarjeta 6 -->
        <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="250">
          <div class="qcard h-100 d-flex flex-column">
            <div class="qcard__top">
              <div class="qcard__label"><i class="fas fa-cart-shopping" style="background: rgba(253,126,20,0.1); color: #fd7e14;"></i> Aprender a Vender Online</div>
            </div>
            <p class="qcard__desc flex-grow-1">Vende plantillas, recursos y servicios digitales</p>
            <div class="qcard__actions mt-auto">
              <button class="qbtn qbtn--primary w-100" style="background: var(--dark-purple);" onclick="searchCourse('Comercio Electrónico')">Comercio Electrónico</button>
              <button class="qbtn qbtn--ghost w-100" onclick="searchCourse('E-Commerce')">Certificado Marketing y E-Commerce (Beca)</button>
            </div>
          </div>
        </div>

        <!-- Tarjeta 7 -->
        <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="300">
          <div class="qcard h-100 d-flex flex-column">
            <div class="qcard__top">
              <div class="qcard__label"><i class="fas fa-handshake" style="background: rgba(32,201,151,0.1); color: #20c997;"></i> Networking y Mentoría</div>
            </div>
            <p class="qcard__desc flex-grow-1">Conecta con otros emprendedores y mentores</p>
            <div class="qcard__actions mt-auto">
              <button class="qbtn qbtn--primary w-100" onclick="searchCourse('REDi')">REDi Puerto Vallarta</button>
              <a href="https://www.instagram.com/redipuertovallarta" target="_blank" class="qbtn qbtn--ghost w-100 d-block mt-2">Instagram: @redipuertovallarta</a>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Separador sutil opcional -->
    <div style="height: 1px; background: rgba(0,0,0,0.05); margin: 1rem auto 3rem auto; width: 80%; max-width: 1000px;"></div>

    <div class="container" id="catalogo">
      
      <!-- Buscador -->
      <div class="row mb-4 justify-content-center" id="buscador-section" data-aos="fade-up" data-aos-delay="100">
        <div class="col-12 col-md-8 col-lg-6">
          <div class="input-group shadow-sm" style="border-radius: 999px; overflow: hidden; border: 1px solid rgba(0,0,0,0.1); background: #fff;">
            <span class="input-group-text bg-white border-0 text-muted ps-4" style="background: #fff; border-right: none;"><i class="fas fa-search"></i></span>
            <input type="text" id="courseSearchInput" class="form-control border-0 py-3 ps-2" placeholder="Buscar cursos por nombre o institución..." style="box-shadow: none; font-family: 'Montserrat', sans-serif;">
          </div>
        </div>
      </div>

      <!-- Tabs principales -->
      <ul class="nav nav-pills justify-content-center gap-2 mb-4" id="mainTabs" role="tablist" data-aos="fade-up" data-aos-delay="150">
    <li class="nav-item" role="presentation">
      <button class="nav-link active section-pill" id="tab-gratuitos" data-bs-toggle="pill" data-bs-target="#pane-gratuitos" type="button" role="tab">Cursos Gratuitos</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link section-pill" id="tab-paga" data-bs-toggle="pill" data-bs-target="#pane-paga" type="button" role="tab">Cursos de Paga</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link section-pill" id="tab-fin" data-bs-toggle="pill" data-bs-target="#pane-fin" type="button" role="tab">Financiamiento</button>
    </li>
  </ul>

  <div class="tab-content" id="mainTabsContent">

    <!-- GRATUITOS -->
    <div class="tab-pane fade show active" id="pane-gratuitos" role="tabpanel" aria-labelledby="tab-gratuitos">
      <ul class="nav nav-pills justify-content-center gap-2 mb-3 subpill" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#pane-digital" type="button" role="tab">Digitales</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pane-presencial" type="button" role="tab">Presenciales</button>
        </li>
      </ul>

      <div class="tab-content">
        <div class="tab-pane fade show active" id="pane-digital" role="tabpanel">
          <div class="row g-4">
            <?php if (count($gratuitos_digital) > 0): ?>
              <?php foreach ($gratuitos_digital as $c) { render_course_card($c, 'free'); } ?>
            <?php else: ?>
              <div class="col-12 text-center"><p class="text-muted">No hay cursos digitales gratuitos por ahora.</p></div>
            <?php endif; ?>
          </div>
        </div>

        <div class="tab-pane fade" id="pane-presencial" role="tabpanel">
          <div class="row g-4">
            <?php if (count($gratuitos_presencial) > 0): ?>
              <?php foreach ($gratuitos_presencial as $c) { render_course_card($c, 'free'); } ?>
            <?php else: ?>
              <div class="col-12 text-center"><p class="text-muted">No hay cursos presenciales gratuitos por ahora.</p></div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- PAGA -->
    <div class="tab-pane fade" id="pane-paga" role="tabpanel" aria-labelledby="tab-paga">

      <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
        <button class="filter-chip active js-pay-filter" data-filter="all">Todos</button>
        <button class="filter-chip js-pay-filter" data-filter="diseno">Diseño</button>
        <button class="filter-chip js-pay-filter" data-filter="ux">UX/UI</button>
        <button class="filter-chip js-pay-filter" data-filter="animacion">Animación</button>
        <button class="filter-chip js-pay-filter" data-filter="tecnologia">Tecnología</button>
        <button class="filter-chip js-pay-filter" data-filter="marketing">Marketing</button>
        <button class="filter-chip js-pay-filter" data-filter="finanzas">Finanzas</button>
        <button class="filter-chip js-pay-filter" data-filter="otros">Otros</button>
      </div>

      <ul class="nav nav-pills justify-content-center gap-2 mb-3 subpill" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#pane-pay-digital" type="button" role="tab">Digitales</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pane-pay-presencial" type="button" role="tab">Presenciales</button>
        </li>
      </ul>

      <div class="tab-content">
        <div class="tab-pane fade show active" id="pane-pay-digital" role="tabpanel">
          <div class="row g-4">
            <?php if (count($paga_digital) > 0): ?>
              <?php foreach ($paga_digital as $c) { render_course_card($c, 'pay'); } ?>
            <?php else: ?>
              <div class="col-12 text-center"><p class="text-muted">No hay cursos digitales de paga por ahora.</p></div>
            <?php endif; ?>
          </div>
        </div>

        <div class="tab-pane fade" id="pane-pay-presencial" role="tabpanel">
          <div class="row g-4">
            <?php if (count($paga_presencial) > 0): ?>
              <?php foreach ($paga_presencial as $c) { render_course_card($c, 'pay'); } ?>
            <?php else: ?>
              <div class="col-12 text-center"><p class="text-muted">No hay cursos presenciales de paga por ahora.</p></div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- FINANCIAMIENTO -->
    <div class="tab-pane fade" id="pane-fin" role="tabpanel" aria-labelledby="tab-fin">
      <div class="row g-4">
        <?php if (count($financiamiento) > 0): ?>
          <?php foreach ($financiamiento as $c) { render_course_card($c, 'fin'); } ?>
        <?php else: ?>
          <div class="col-12 text-center"><p class="text-muted">No hay recursos de financiamiento por ahora.</p></div>
        <?php endif; ?>
      </div>
    </div>

    </div>
      </div>
    </div>
  </section>

  <!-- Footer (ligero, consistente) -->
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
                <div class="footer-col">
                    <h3>Enlaces Rápidos</h3>
                    <a href="index.php">Inicio</a>
                    <a href="cursos.php">Cursos</a>
                    <a href="nosotros.php">Nosotros</a>
                    <a href="foro.php">Foro</a>
                </div>
                <div class="footer-col">
                    <h3>Cursos</h3>
                    <a href="cursos.php#gratuitos">Cursos Gratuitos</a>
                    <a href="cursos.php#paga">Cursos de Paga</a>
                    <a href="#">Certificaciones</a>
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
<script>
  AOS.init({ duration: 800, once: true });

  // Filtro simple para pestaña Paga (solo UI)
  function applyPayFilter(filter){
    const pane = document.getElementById('pane-paga');
    if (!pane) return;

    pane.querySelectorAll('.course-card2').forEach(card => {
      const cat = card.dataset.category || 'otros';
      const col = card.closest('.col-12');
      if (!col) return;
      const show = (filter === 'all') || (cat === filter);
      col.classList.toggle('d-none', !show);
    });
  }

  document.querySelectorAll('.js-pay-filter').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.js-pay-filter').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      applyPayFilter(btn.dataset.filter || 'all');
    });
  });

  // Cuando cambias de sub-tab en Paga, re-aplica el filtro actual
  document.getElementById('pane-paga')?.addEventListener('shown.bs.tab', () => {
    const active = document.querySelector('.js-pay-filter.active');
    applyPayFilter(active?.dataset.filter || 'all');
  });
</script>
<script>
document.addEventListener('change', async (e) => {
  const el = e.target;
  if (!el.classList.contains('course-checkbox')) return;

  // SOLO cuando se marca
  if (!el.checked) return;

  const body = new URLSearchParams();
  body.set('course_id', el.dataset.courseId || '');
  body.set('checked', '1');

  try {
    const r = await fetch('api/save_courses.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body
    });

    if (!r.ok) {
      // si falla, revierte el check para no mentirle al usuario
      el.checked = false;
      return;
    }

    const data = await r.json().catch(() => null);
    if (!data || data.ok !== true) el.checked = false;

  } catch (err) {
    el.checked = false;
  }
});

    // Efecto navbar al hacer scroll (igual lógica que scripts.js)
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
</script>

  <!-- Registration/Login Modal -->
  <div class="register-modal" id="registerModal" role="dialog" aria-modal="true" aria-labelledby="registerTitle" aria-hidden="true">
    <div class="register-content">
      <button class="register-close" id="registerClose" aria-label="Cerrar" onclick="closeRegisterModal()">&times;</button>
      
      <!-- Registration Form Container -->
      <div id="registerFormContainer">
        <div class="register-header">
          <h2 id="registerTitle">Regístrate para recibir información</h2>
          <p>Completa tus datos para acceder a nuestro catálogo completo</p>
        </div>
        <form action="config/register.php" method="POST">
              <div class="form-group">
                <label>Nombre completo</label>
                <input type="text" name="nombre" class="form-control" placeholder="Tu nombre" required />
              </div>
              <div class="form-group">
                <label>Correo Electrónico</label>
                <input type="email" name="correo" class="form-control" placeholder="ejemplo@correo.com" required />
              </div>
              <div class="form-group">
                <label>Número Telefónico</label>
                <input type="tel" name="telefono" class="form-control" placeholder="+52 322 123 4567" />
              </div>
              <div class="form-group">
                <label>Ciudad</label>
                <input type="text" name="ciudad" class="form-control" placeholder="Puerto Vallarta" />
              </div>
              <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" class="form-control" placeholder="********" required />
              </div>
              <button type="submit" class="register-btn">Registrarse</button>
        </form>
        <div class="text-center mt-3">
            <button type="button" class="register-btn" style="background-color: transparent; color: var(--bright-blue); border: 2px solid var(--bright-blue);" onclick="toggleForms('login')">¿Ya tienes cuenta? Iniciar Sesión</button>
        </div>
        <div class="register-footer">
          <p>Al registrarte aceptas nuestra <a href="#" rel="noopener">Política de Privacidad</a></p>
        </div>
      </div>

      <!-- Login Form Container (Initially Hidden) -->
      <div id="loginFormContainer" style="display: none;">
        <div class="register-header">
          <h2>Iniciar Sesión</h2>
          <p>Bienvenido de nuevo</p>
        </div>
        <form action="config/login.php" method="POST">
              <div class="form-group">
                <label>Correo Electrónico</label>
                <input type="email" name="correo" class="form-control" placeholder="ejemplo@correo.com" required />
              </div>
              <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" class="form-control" placeholder="********" required />
              </div>
              <button type="submit" class="register-btn">Iniciar Sesión</button>
        </form>
        <div class="text-center mt-3">
            <button type="button" class="register-btn" style="background-color: transparent; color: var(--bright-blue); border: 2px solid var(--bright-blue);" onclick="toggleForms('register')">¿No tienes cuenta? Registrarse</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    function searchCourse(query) {
      const searchInput = document.getElementById('courseSearchInput');
      if(searchInput) {
          searchInput.value = query;
          // Trigger input event to filter
          searchInput.dispatchEvent(new Event('input'));
          // Scroll to the catalog
          const catalogo = document.getElementById('catalogo');
          if(catalogo) {
              const yOffset = -100; // offset to not hide under header
              const y = catalogo.getBoundingClientRect().top + window.pageYOffset + yOffset;
              window.scrollTo({top: y, behavior: 'smooth'});
          }
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('courseSearchInput');
        if(searchInput) {
            searchInput.addEventListener('input', function(e) {
                const term = e.target.value.toLowerCase().trim();
                const items = document.querySelectorAll('.course-item');
                
                // If filtering logic is needed globally regardless of tabs
                // Normally tabs hide elements via Bootstrap, but Javascript can filter within them
                items.forEach(item => {
                    const title = item.querySelector('h5')?.innerText.toLowerCase() || '';
                    const provider = item.querySelector('.course-meta strong')?.innerText.toLowerCase() || '';
                    if(term === '' || title.includes(term) || provider.includes(term)) {
                        item.classList.remove('d-none');
                    } else {
                        item.classList.add('d-none');
                    }
                });
            });
        }
    });

    window.isLoggedIn = <?php echo isset($_SESSION['usuario_id']) ? 'true' : 'false'; ?>;
  </script>
  <script src="assets/js/scripts.js"></script>
</body>
</html>
