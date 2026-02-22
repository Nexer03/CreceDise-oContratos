<?php
session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Auth check
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Admin check (no confíes en el frontend)
$isAdmin = 0;
if (isset($_SESSION['is_admin'])) {
    $isAdmin = (int)$_SESSION['is_admin'];
} else {
    try {
        $stAdmin = $pdo->prepare("SELECT is_admin FROM usuarios WHERE id = :id LIMIT 1");
        $stAdmin->execute([':id' => (int)$_SESSION['usuario_id']]);
        $rowAdmin = $stAdmin->fetch(PDO::FETCH_ASSOC);
        $isAdmin = (int)($rowAdmin['is_admin'] ?? 0);
        $_SESSION['is_admin'] = $isAdmin;
    } catch (PDOException $e) {
        $isAdmin = 0;
    }
}

if (empty($isAdmin)) {
    http_response_code(403);
    die("Acceso denegado.");
}

$catalog = require __DIR__ . '/config/catalog.php';

// Helpers
function fetchColumnInt(PDO $pdo, string $sql, array $params = []): int {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return (int)$st->fetchColumn();
}

function fetchColumnFloat(PDO $pdo, string $sql, array $params = []): float {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return (float)$st->fetchColumn();
}

// Inputs
$q = trim($_GET['q'] ?? '');
$p_q = trim($_GET['p_q'] ?? '');
$userFilterId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Pagination
$usersPage = max(1, (int)($_GET['u_page'] ?? 1));
$payPage   = max(1, (int)($_GET['p_page'] ?? 1));

$usersPerPage = 10;
$payPerPage   = 15;

$usersOffset = ($usersPage - 1) * $usersPerPage;
$payOffset   = ($payPage - 1) * $payPerPage;

// KPIs (sin desglose por estado)
$totalUsers = fetchColumnInt($pdo, "SELECT COUNT(*) FROM usuarios");
$usersToday = fetchColumnInt($pdo, "SELECT COUNT(*) FROM usuarios WHERE DATE(fecha_registro)=CURDATE()");
$totalPayments = fetchColumnInt($pdo, "SELECT COUNT(*) FROM payments");
$completedPayments = fetchColumnInt($pdo, "SELECT COUNT(*) FROM payments WHERE status='COMPLETED'");
$totalRevenue = fetchColumnFloat($pdo, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='COMPLETED'");
$revenueMonth = fetchColumnFloat($pdo, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='COMPLETED' AND YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE())");

// USERS (list + search)
$userWhere = "";
$userParams = [];
if ($q !== '') {
    $userWhere = "WHERE u.nombre LIKE :q OR u.correo LIKE :q";
    $userParams[':q'] = "%{$q}%";
}

$totalUsersFiltered = fetchColumnInt($pdo, "SELECT COUNT(*) FROM usuarios u $userWhere", $userParams);

$userSql = "
SELECT 
  u.id, u.nombre, u.correo, u.telefono, u.ciudad, u.fecha_registro,
  COUNT(p.id) AS compras_total,
  COALESCE(SUM(CASE WHEN p.status='COMPLETED' THEN p.amount ELSE 0 END),0) AS gastado_total,
  MAX(p.created_at) AS ultima_compra
FROM usuarios u
LEFT JOIN payments p ON p.usuario_id = u.id
$userWhere
GROUP BY u.id, u.nombre, u.correo, u.fecha_registro
ORDER BY u.id DESC
LIMIT :lim OFFSET :off
";

$stUsers = $pdo->prepare($userSql);
foreach ($userParams as $k => $v) $stUsers->bindValue($k, $v, PDO::PARAM_STR);
$stUsers->bindValue(':lim', (int)$usersPerPage, PDO::PARAM_INT);
$stUsers->bindValue(':off', (int)$usersOffset, PDO::PARAM_INT);
$stUsers->execute();
$users = $stUsers->fetchAll(PDO::FETCH_ASSOC);
// Usuario con más compras
$topUser = null;

try {
    $stmtTop = $pdo->query("
        SELECT u.nombre, u.correo, COUNT(p.id) total
        FROM payments p
        JOIN usuarios u ON u.id = p.usuario_id
        GROUP BY p.usuario_id
        ORDER BY total DESC
        LIMIT 1
    ");
    $topUser = $stmtTop->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $topUser = null;
}

// PAYMENTS (global or by user/search)
$payWhere = "";
$payParams = [];

if ($p_q !== '') {
    $payWhere = "WHERE (u.nombre LIKE :pq OR u.correo LIKE :pq OR p.payer_email LIKE :pq)";
    $payParams[':pq'] = "%{$p_q}%";
}

if ($userFilterId > 0) {
    if ($payWhere === "") {
        $payWhere = "WHERE p.usuario_id = :uid";
    } else {
        $payWhere .= " AND p.usuario_id = :uid";
    }
    $payParams[':uid'] = $userFilterId;
}

$totalPaymentsFiltered = fetchColumnInt($pdo, "SELECT COUNT(*) FROM payments p JOIN usuarios u ON u.id = p.usuario_id $payWhere", $payParams);

$paySql = "
SELECT 
  p.id, p.usuario_id, p.order_id, p.payer_email, p.amount, p.currency, p.status, p.product, p.created_at,
  u.nombre, u.correo
FROM payments p
JOIN usuarios u ON u.id = p.usuario_id
$payWhere
ORDER BY p.id DESC
LIMIT :lim OFFSET :off
";

$stPays = $pdo->prepare($paySql);
foreach ($payParams as $k => $v) {
    if ($k === ':uid') {
        $stPays->bindValue($k, $v, PDO::PARAM_INT);
    } else {
        $stPays->bindValue($k, $v, PDO::PARAM_STR);
    }
}
$stPays->bindValue(':lim', (int)$payPerPage, PDO::PARAM_INT);
$stPays->bindValue(':off', (int)$payOffset, PDO::PARAM_INT);
$stPays->execute();
$payments = $stPays->fetchAll(PDO::FETCH_ASSOC);

// Selected user details (for header)
$selectedUser = null;
if ($userFilterId > 0) {
    $stSel = $pdo->prepare("SELECT id, nombre, correo FROM usuarios WHERE id = :id LIMIT 1");
    $stSel->execute([':id' => $userFilterId]);
    $selectedUser = $stSel->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Pagination helpers
function buildUrl(array $overrides = []): string {
    $params = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === null) unset($params[$k]);
        else $params[$k] = $v;
    }
    $qs = http_build_query($params);
    return basename($_SERVER['PHP_SELF']) . ($qs ? ("?".$qs) : "");
}

function renderPagination(int $total, int $perPage, int $currentPage, string $pageKey): string {
    $totalPages = (int)ceil($total / max(1, $perPage));
    if ($totalPages <= 1) return '';

    $html = '<nav class="mt-3"><ul class="pagination justify-content-center flex-wrap gap-1">';

    $prev = max(1, $currentPage - 1);
    $next = min($totalPages, $currentPage + 1);

    $isDisabledPrev = $currentPage <= 1 ? ' disabled' : '';
    $isDisabledNext = $currentPage >= $totalPages ? ' disabled' : '';

    $html .= '<li class="page-item'.$isDisabledPrev.'"><a class="page-link" href="'.htmlspecialchars(buildUrl([$pageKey => $prev])).'">Anterior</a></li>';

    // Windowed pages
    $window = 2;
    $start = max(1, $currentPage - $window);
    $end = min($totalPages, $currentPage + $window);

    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="'.htmlspecialchars(buildUrl([$pageKey => 1])).'">1</a></li>';
        if ($start > 2) $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $currentPage ? ' active' : '';
        $html .= '<li class="page-item'.$active.'"><a class="page-link" href="'.htmlspecialchars(buildUrl([$pageKey => $i])).'">'.$i.'</a></li>';
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        $html .= '<li class="page-item"><a class="page-link" href="'.htmlspecialchars(buildUrl([$pageKey => $totalPages])).'">'.$totalPages.'</a></li>';
    }

    $html .= '<li class="page-item'.$isDisabledNext.'"><a class="page-link" href="'.htmlspecialchars(buildUrl([$pageKey => $next])).'">Siguiente</a></li>';
    $html .= '</ul></nav>';

    return $html;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Crece Diseño - Panel Admin</title>

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

      /* Admin-specific */
      .kpi-grid .kpi {
        background: #f8fafc;
        border: 1px solid rgba(0,0,0,0.06);
        border-radius: 14px;
        padding: 16px 16px;
        height: 100%;
      }
      .kpi .label { color: #667085; font-size: .9rem; }
      .kpi .value { font-size: 1.6rem; font-weight: 800; color: #1A1C36; line-height: 1.1; }
      .kpi .sub { color: #667085; font-size: .85rem; margin-top: 4px; }

      .section-title {
        font-family: 'Quicksand', sans-serif;
        font-weight: 800;
        color: #1A1C36;
        margin-bottom: 10px;
      }
      .soft-input {
        border-radius: 999px;
        padding: 10px 14px;
        border: 1px solid rgba(0,0,0,0.12);
      }
      .soft-input:focus { box-shadow: none; border-color: #217CE3; }
      .pill-btn {
        border-radius: 999px;
        padding: 10px 16px;
        font-weight: 700;
      }
      .page-link { border-radius: 10px; }
      :root { --nav-offset: 96px; } /* fallback */
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
                <?php if (!empty($isAdmin)): ?>
                <a href="admin_analitica.php" class="admin-btn active">
                  <i class="fas fa-chart-line"></i> Panel Admin
                </a>
                <a href="admin_catalogo.php" class="admin-btn">
                 <i class="fas fa-tags"></i> Editar Catálogo 
                </a>
              <?php endif; ?>
                <a href="analitica.php" class="history-btn">
                  <i class="fas fa-history"></i> Historial de Compras
                </a>
                <a href="config/logout.php" class="logout-btn">
                  <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
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

  <!-- HERO -->
  <section class="contracts-hero" id="inicio">
      <div class="banner-container"><img src="patron1.svg" alt="Patrón de fondo" /></div>
      <div class="container" data-aos="fade-up">
          <div class="contracts-hero-content text-center">
            <h1 class="contracts-main-title">Panel de <span class="highlight-gradient">Administración</span> <i class="fa-solid fa-chart-line title-icon"></i></h1>
            <p class="contracts-subtitle" style="margin: 0 auto;">Estadísticas de usuarios, compras y detalle por usuario.</p>
          </div>
      </div>
  </section>

  <section class="history-container">
      <div class="container">

          <!-- KPIs -->
          <div class="table-card mb-4" data-aos="fade-up" data-aos-delay="80">
              <div class="row g-3 kpi-grid">
                  <div class="col-6 col-lg-2">
                      <div class="kpi">
                          <div class="label">Usuarios</div>
                          <div class="value"><?php echo (int)$totalUsers; ?></div>
                          <div class="sub">Pagos hoy: <?php echo (int)$usersToday; ?></div>
                      </div>
                  </div>
                  <div class="col-6 col-lg-2">
                      <div class="kpi">
                          <div class="label">Pagos Totales</div>
                          <div class="value"><?php echo (int)$totalPayments; ?></div>
                          <div class="sub">Completados: <?php echo (int)$completedPayments; ?></div>
                      </div>
                  </div>
                  <div class="col-12 col-lg-4">
                      <div class="kpi">
                          <div class="label">Ingresos totales</div>
                          <div class="value">$<?php echo number_format((float)$totalRevenue, 2); ?> MXN</div>
                          <div class="sub">Mes: $<?php echo number_format((float)$revenueMonth, 2); ?> MXN</div>
                      </div>
                  </div>
                  <div class="col-12 col-lg-4">
                    <div class="kpi">
                        <div class="label">Usuario con más compras</div>

                        <?php if ($topUser): ?>
                            <div class="value">
                                <?php echo htmlspecialchars($topUser['nombre']); ?>
                            </div>
                            <div class="sub">
                                <?php echo (int)$topUser['total']; ?> compras
                            </div>
                            <div class="sub text-muted">
                                <?php echo htmlspecialchars($topUser['correo']); ?>
                            </div>
                        <?php else: ?>
                            <div class="sub">Sin datos aún</div>
                        <?php endif; ?>
                    </div>
                </div>
              </div>
          </div>

          <!-- USERS -->
          <div class="table-card mb-4" data-aos="fade-up" data-aos-delay="120">
              <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                  <div>
                      <div class="section-title mb-0">Usuarios</div>
                      <div class="text-muted small">Busca por nombre o correo y revisa su historial.</div>
                  </div>

                  <form class="d-flex gap-2" method="get" action="admin_analitica.php">
                      <input type="hidden" name="p_page" value="1">
                      <input type="hidden" name="u_page" value="1">
                      <?php if($userFilterId>0): ?>
                        <input type="hidden" name="user_id" value="<?php echo (int)$userFilterId; ?>">
                      <?php endif; ?>
                      <input class="form-control soft-input" type="text" name="q" placeholder="Buscar usuario..." value="<?php echo htmlspecialchars($q); ?>" />
                      <button class="btn btn-primary pill-btn" type="submit"><i class="fas fa-search me-1"></i> Buscar</button>
                      <?php if($q !== ''): ?>
                        <a class="btn btn-outline-secondary pill-btn" href="<?php echo htmlspecialchars(buildUrl(['q'=>null,'u_page'=>1])); ?>"><i class="fas fa-times me-1"></i> Limpiar</a>
                      <?php endif; ?>
                  </form>
              </div>

              <div class="table-responsive">
                  <table class="table table-hover align-middle">
                      <thead class="table-light">
                          <tr>
                              <th style="min-width:260px;">Usuario</th>
                              <th>Registrado</th>
                              <th>Compras</th>
                              <th>Gastado</th>
                              <th>Última compra</th>
                              <th>Acciones</th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php if(empty($users)): ?>
                              <tr><td colspan="6" class="text-center text-muted py-4">Sin resultados.</td></tr>
                          <?php else: ?>
                              <?php foreach($users as $u):
                                  $reg = !empty($u['fecha_registro']) ? date('d/m/Y H:i', strtotime($u['fecha_registro'])) : '—';
                                  $ult = !empty($u['ultima_compra']) ? date('d/m/Y H:i', strtotime($u['ultima_compra'])) : '—';
                              ?>
                              <tr>
                                  <td>
                                      <div class="fw-semibold"><?php echo htmlspecialchars($u['nombre'] ?? ''); ?></div>
                                      <div class="text-muted small"><?php echo htmlspecialchars($u['correo'] ?? ''); ?></div>
                                  </td>
                                  <td><?php echo htmlspecialchars($reg); ?></td>
                                  <td><strong><?php echo (int)($u['compras_total'] ?? 0); ?></strong></td>
                                  <td><strong>$<?php echo number_format((float)($u['gastado_total'] ?? 0), 2); ?> MXN</strong></td>
                                  <td><?php echo htmlspecialchars($ult); ?></td>
                                  <td>
                                      <div class="d-flex gap-2">
                                          <button class="btn btn-sm btn-outline-warning rounded-pill flex-fill text-center edit-user-btn"
                                                  data-id="<?php echo (int)$u['id']; ?>"
                                                  data-nombre="<?php echo htmlspecialchars($u['nombre'] ?? ''); ?>"
                                                  data-correo="<?php echo htmlspecialchars($u['correo'] ?? ''); ?>"
                                                  data-telefono="<?php echo htmlspecialchars($u['telefono'] ?? ''); ?>"
                                                  data-ciudad="<?php echo htmlspecialchars($u['ciudad'] ?? ''); ?>"
                                                  data-bs-toggle="modal" data-bs-target="#editUserModal">
                                              <i class="fas fa-user-edit me-1"></i> Editar
                                          </button>
                                      </div>
                                  </td>
                              </tr>
                              <?php endforeach; ?>
                          <?php endif; ?>
                      </tbody>
                  </table>
              </div>

              <?php echo renderPagination($totalUsersFiltered, $usersPerPage, $usersPage, 'u_page'); ?>
          </div>

          <!-- PAYMENTS -->
          <div class="table-card" id="compras" data-aos="fade-up" data-aos-delay="160">
              <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                  <div>
                      <div class="section-title mb-0">
                        <?php if($selectedUser): ?>
                          Compras de <?php echo htmlspecialchars($selectedUser['nombre']); ?>
                        <?php else: ?>
                          Compras (todas)
                        <?php endif; ?>
                      </div>
                      <div class="text-muted small">
                        <?php if($selectedUser): ?>
                          <?php echo htmlspecialchars($selectedUser['correo']); ?> — <a href="<?php echo htmlspecialchars(buildUrl(['user_id'=>null,'p_page'=>1])); ?>">ver todas</a>
                        <?php else: ?>
                          Lista paginada de pagos en la plataforma.
                        <?php endif; ?>
                      </div>
                  </div>

                  <div class="d-flex align-items-center gap-2">
                    <?php if($selectedUser): ?>
                      <a class="btn btn-outline-secondary pill-btn" href="<?php echo htmlspecialchars(buildUrl(['user_id'=>null,'p_page'=>1])); ?>">
                        <i class="fas fa-arrow-left me-1"></i> Quitar filtro
                      </a>
                    <?php endif; ?>

                    <form method="GET" action="admin_analitica.php" class="d-flex align-items-center gap-2 m-0">
                        <?php if($q !== ''): ?>
                          <input type="hidden" name="q" value="<?php echo htmlspecialchars($q); ?>">
                        <?php endif; ?>
                        <?php if($userFilterId > 0): ?>
                          <input type="hidden" name="user_id" value="<?php echo (int)$userFilterId; ?>">
                        <?php endif; ?>
                        <input class="form-control soft-input" type="text" name="p_q" placeholder="Buscar compra o usuario..." value="<?php echo htmlspecialchars($p_q); ?>" />
                        <button class="btn btn-primary pill-btn" type="submit"><i class="fas fa-search"></i> Buscar</button>
                        <?php if($p_q !== ''): ?>
                          <a class="btn btn-outline-secondary pill-btn" href="<?php echo htmlspecialchars(buildUrl(['p_q'=>null,'p_page'=>1])); ?>"><i class="fas fa-times me-1"></i></a>
                        <?php endif; ?>
                    </form>
                  </div>
              </div>

              <div class="table-responsive">
                  <table class="table table-hover align-middle">
                      <thead class="table-light">
                          <tr>
                              <th>ID</th>
                              <th style="min-width:240px;">Usuario</th>
                              <th style="min-width:240px;">Producto</th>
                              <th>Monto</th>
                              <th>Fecha</th>
                              <th>Order</th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php if(empty($payments)): ?>
                              <tr><td colspan="6" class="text-center text-muted py-4">Sin pagos para mostrar.</td></tr>
                          <?php else: ?>
                              <?php foreach($payments as $pay):
                                  $productKey = $pay['product'] ?? '';
                                  $title = $catalog[$productKey]['title'] ?? ucfirst(str_replace('_', ' ', $productKey));
                                  if (empty($title)) $title = "Producto Desconocido";

                                  $amount = $pay['amount'] ?? 0;
                                  $currency = $pay['currency'] ?? 'MXN';
                                  $price = number_format((float)$amount, 2);

                                  $dateStr = '—';
                                  if (!empty($pay['created_at'])) {
                                      $dateStr = date('d/m/Y H:i', strtotime($pay['created_at']));
                                  }
                              ?>
                              <tr>
                                  <td><?php echo (int)$pay['id']; ?></td>
                                  <td>
                                      <div class="fw-semibold"><?php echo htmlspecialchars($pay['nombre'] ?? ''); ?></div>
                                      <div class="text-muted small"><?php echo htmlspecialchars($pay['correo'] ?? ''); ?></div>
                                  </td>
                                  <td>
                                      <div class="d-flex align-items-center">
                                          <div class="me-3 bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; color: var(--dark-blue);">
                                              <i class="fas fa-file-contract"></i>
                                          </div>
                                          <div>
                                              <strong><?php echo htmlspecialchars($title); ?></strong>
                                              <div class="small text-muted">Correo comprador: <?php echo htmlspecialchars($pay['payer_email'] ?? 'N/A'); ?></div>
                                          </div>
                                      </div>
                                  </td>
                                  <td><strong>$<?php echo $price . ' ' . htmlspecialchars($currency); ?></strong></td>
                                  <td><?php echo htmlspecialchars($dateStr); ?></td>
                                  <td class="text-muted small"><?php echo htmlspecialchars($pay['order_id'] ?? ''); ?></td>
                              </tr>
                              <?php endforeach; ?>
                          <?php endif; ?>
                      </tbody>
                  </table>
              </div>

              <?php echo renderPagination($totalPaymentsFiltered, $payPerPage, $payPage, 'p_page'); ?>
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
  <script>
    window.isLoggedIn = <?php echo isset($_SESSION['usuario_id']) ? 'true' : 'false'; ?>;
  </script>
  <!-- Edit User Modal -->
  <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form action="config/admin_edit_user.php" method="POST">
          <div class="modal-header">
            <h5 class="modal-title" id="editUserModalLabel">Editar Usuario</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="user_id" id="editUserId">
            
            <div class="mb-3">
              <label for="editNombre" class="form-label">Nombre Completo</label>
              <input type="text" class="form-control" id="editNombre" name="nombre" required>
            </div>
            
            <div class="mb-3">
              <label for="editCorreo" class="form-label">Correo Electrónico</label>
              <input type="email" class="form-control" id="editCorreo" name="correo" required>
            </div>
            
            <div class="mb-3">
              <label for="editTelefono" class="form-label">Teléfono</label>
              <input type="text" class="form-control" id="editTelefono" name="telefono">
            </div>
            
            <div class="mb-3">
              <label for="editCiudad" class="form-label">Ciudad</label>
              <input type="text" class="form-control" id="editCiudad" name="ciudad">
            </div>
            
            <hr>
            
            <div class="mb-3">
              <label for="editPassword" class="form-label">Nueva Contraseña <small class="text-muted">(Opcional)</small></label>
              <input type="password" class="form-control" id="editPassword" name="password" placeholder="Dejar en blanco para no cambiar">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
  <script src="scripts.js"></script>
  <script>
    // Initialize AOS
    AOS.init({ once: true, offset: 50 });

    // Handle Edit User Modal population
    document.addEventListener('DOMContentLoaded', () => {
      const editButtons = document.querySelectorAll('.edit-user-btn');
      editButtons.forEach(btn => {
        btn.addEventListener('click', () => {
          document.getElementById('editUserId').value = btn.getAttribute('data-id');
          document.getElementById('editNombre').value = btn.getAttribute('data-nombre');
          document.getElementById('editCorreo').value = btn.getAttribute('data-correo');
          document.getElementById('editTelefono').value = btn.getAttribute('data-telefono') !== 'null' ? btn.getAttribute('data-telefono') : '';
          document.getElementById('editCiudad').value = btn.getAttribute('data-ciudad') !== 'null' ? btn.getAttribute('data-ciudad') : '';
          document.getElementById('editPassword').value = ''; // Always clear password field
        });
      });

    const nav = document.querySelector('.custom-navbar');
    const setOffset = () => {
      const h = nav ? nav.offsetHeight : 80;
      // +12 para que respire un poco
      document.documentElement.style.setProperty('--nav-offset', (h + 12) + 'px');
    };
    setOffset();
    window.addEventListener('resize', setOffset);

    <?php if ($p_q !== '' || $payPage > 1 || $userFilterId > 0): ?>
    setTimeout(() => {
      const comprasSec = document.getElementById("compras");
      if(comprasSec) {
        const h = nav ? nav.offsetHeight : 80;
        window.scrollTo({
          top: comprasSec.offsetTop - h - 20,
          behavior: 'smooth'
        });
      }
    }, 100);
    <?php endif; ?>
  });
  </script>
</body>
</html>
