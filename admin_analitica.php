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
  u.id, u.nombre, u.correo, u.fecha_registro,
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

// PAYMENTS (global or by user)
$payWhere = "";
$payParams = [];

if ($userFilterId > 0) {
    $payWhere = "WHERE p.usuario_id = :uid";
    $payParams[':uid'] = $userFilterId;
}

$totalPaymentsFiltered = fetchColumnInt($pdo, "SELECT COUNT(*) FROM payments p $payWhere", $payParams);

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
foreach ($payParams as $k => $v) $stPays->bindValue($k, $v, PDO::PARAM_INT);
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

  <style>
      body {
          background-color: #f5f7fa;
      }
      .hero-section {
        margin-top: 100px;
        padding: 60px 0;
        text-align: center;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      }
      .hero-section h1 {
          font-family: 'Quicksand', sans-serif;
          color: var(--dark-blue);
          font-weight: 700;
          margin-bottom: 1rem;
      }
      .history-container {
          padding: 50px 0;
          min-height: 50vh;
      }
      .table-card {
          background: white;
          border-radius: 15px;
          box-shadow: 0 10px 30px rgba(0,0,0,0.05);
          overflow: hidden;
          padding: 30px;
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
      .user-profile-menu { position: relative; margin-left: 15px; }
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
      .user-toggle:hover { background: #217CE3; color: white; }
      .user-dropdown {
        position: absolute;
        top: 120%;
        right: 0;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        width: 280px;
        padding: 15px;
        display: none;
        flex-direction: column;
        z-index: 1000;
      }
      .user-dropdown.active { display: flex; }
      .user-info { padding-bottom: 10px; border-bottom: 1px solid #eee; margin-bottom: 10px; }
      .user-name { display: block; font-weight: 700; color: #1A1C36; }
      .user-email { font-size: 0.85rem; color: #666; }
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
      .logout-btn:hover { background: #fff5f5; }
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
      .history-btn:hover { background: #f0f4f8; color: #217CE3; }
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
      .admin-btn:hover { background: #f0f4f8; color: #217CE3; }

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

                <a href="admin_analitica.php" class="admin-btn">
                  <i class="fas fa-chart-line"></i> Panel Admin
                </a>

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

  <!-- HERO -->
  <section class="hero-section">
      <div class="container" data-aos="fade-up">
          <h1>Panel de Administración</h1>
          <p>Estadísticas de usuarios, compras y detalle por usuario.</p>
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
                                      <a class="btn btn-sm btn-outline-primary rounded-pill"
                                         href="<?php echo htmlspecialchars(buildUrl(['user_id'=>(int)$u['id'], 'p_page'=>1])); ?>">
                                          <i class="fas fa-receipt me-1"></i> Ver compras
                                      </a>
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
          <div class="table-card" data-aos="fade-up" data-aos-delay="160">
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

                  <?php if($selectedUser): ?>
                    <a class="btn btn-outline-secondary pill-btn" href="<?php echo htmlspecialchars(buildUrl(['user_id'=>null,'p_page'=>1])); ?>">
                      <i class="fas fa-arrow-left me-1"></i> Quitar filtro
                    </a>
                  <?php endif; ?>
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

  <!-- SCRIPTS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
  <script>
      AOS.init();

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
</body>
</html>
