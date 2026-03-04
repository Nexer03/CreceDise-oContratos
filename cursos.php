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

  echo "\n<div class=\"col-12 col-md-6 col-lg-4\">\n";
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
    echo "      <p class=\"text-muted mb-3\">{$desc}</p>\n";
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
  <link rel="stylesheet" href="styles.css">
  <!-- CSS de contratos para reutilizar estilos -->
  <link rel="stylesheet" href="contratos.css">

  <style>
    body{ font-family: 'Montserrat', sans-serif; }

    /* Guía rápida (simple) */
    .quick-simple{ margin: 2.5rem 0; padding: 2rem 1.5rem; background: #f7f8fb; border: 1px solid #e9ecf3; border-radius: 14px; }
    .quick-simple__head{ text-align: center; margin-bottom: 1.25rem; }
    .quick-simple__title{ font-family: 'Quicksand', sans-serif; font-size: 1.6rem; margin: 0 0 .25rem 0; }
    .quick-simple__subtitle{ margin: 0; color: #555; }
    .quick-simple__grid{ display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-top: 1.25rem; }

    .qcard{ background: #fff; border: 1px solid #eef1f6; border-radius: 12px; padding: 1rem; box-shadow: 0 8px 22px rgba(16,24,40,.06); }
    .qcard__top{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; }
    .qcard__label{ display:flex; align-items:center; gap:.6rem; font-weight: 700; color:#1A1C36; }
    .qcard__label i{ width: 34px; height: 34px; display:inline-flex; align-items:center; justify-content:center; border-radius: 10px; background: #eef4ff; color:#217CE3; }
    .qcard__desc{ margin:.75rem 0 1rem 0; color:#555; font-size:.95rem; }
    .qcard__actions{ display:flex; flex-direction:column; gap:.5rem; }

    .qbtn{ display:block; width:100%; padding:.7rem .9rem; border-radius: 12px; text-decoration:none; font-weight: 700; text-align:center; border: 1px solid transparent; }
    .qbtn--primary{ background: #217CE3; color:#fff; }
    .qbtn--ghost{ background: #fff; color:#217CE3; border-color:#cfe0ff; }

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
      .course-meta li{ flex-direction:column; align-items:flex-start; }
      .course-meta strong{ text-align:left; }
    }
  </style>
</head>
<body>

<div class="background-container"></div>

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
            <li class="nav-item"><a class="nav-link active" href="cursos.php">Cursos</a></li>
            <li class="nav-item"><a class="nav-link" href="contratos.php">Contratos</a></li>
            <li class="nav-item"><a class="nav-link" href="nosotros.php">Nosotros</a></li>
          <li class="nav-item"><a class="nav-link" href="index.php#contacto">Contacto</a></li>

            <?php if(isset($_SESSION['usuario_id'])): ?>
            <li class="nav-item user-profile-menu">
              <button class="user-toggle" aria-expanded="false">
                <i class="fas fa-user-circle"></i>
                <span>Hola, <?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? $nombre_usuario); ?></span>
                <i class="fas fa-chevron-down small"></i>
              </button>
              <div class="user-dropdown">
                <div class="user-info">
                  <span class="user-name"><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? $nombre_usuario); ?></span>
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
                <?php endif; ?>
                <a href="analitica.php" class="dropdown-item mb-1" style="text-decoration: none; color: var(--dark-blue); font-weight: 600; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 6px; transition: background 0.2s;">
                  <i class="fas fa-history"></i> Historial de Compras
                </a>
                <a href="config/logout.php" class="logout-btn">
                  <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
              </div>
            </li>
            <?php else: ?>
            <li class="nav-item ms-lg-2">
              <button class="btn btn-primary btn-cta" onclick="showRegisterModal()">
                Registrarse / Iniciar Sesión
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
      <img src="patron1.svg" alt="Patrón de fondo" />
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

  <!-- LISTADO DE CURSOS -->
  <section class="contracts-list">
    <div class="container">

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
    const r = await fetch('save_courses.php', {
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
    window.isLoggedIn = <?php echo isset($_SESSION['usuario_id']) ? 'true' : 'false'; ?>;
  </script>
  <script src="scripts.js"></script>
</body>
</html>