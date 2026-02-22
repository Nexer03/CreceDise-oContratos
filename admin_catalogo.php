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
  $lines = [];
  $lines[] = "<?php";
  $lines[] = "return [";

  foreach ($catalog as $key => $item) {
    $k = addslashes($key);
    $price = addslashes((string)($item['price'] ?? '0.00'));
    $title = addslashes((string)($item['title'] ?? $key));
    $desc = addslashes((string)($item['description'] ?? ''));
    $icon = addslashes((string)($item['icon'] ?? 'fa-solid fa-file'));
    $tags = addslashes((string)($item['tags'] ?? 'all'));
    $url = addslashes((string)($item['url'] ?? ''));
    $images = (int)($item['images'] ?? 0);
    
    $lines[] = "  '{$k}' => [";
    $lines[] = "    'price' => '{$price}',";
    $lines[] = "    'title' => '{$title}',";
    $lines[] = "    'description' => '{$desc}',";
    $lines[] = "    'icon' => '{$icon}',";
    $lines[] = "    'tags' => '{$tags}',";
    $lines[] = "    'url' => '{$url}',";
    $lines[] = "    'images' => {$images}";
    $lines[] = "  ],";
  }

  $lines[] = "];";
  $content = implode("\n", $lines) . "\n";

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

// Recursively delete directory
function deleteDir($dirPath) {
    if (!is_dir($dirPath)) {
        return;
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            deleteDir($file);
        } else {
            unlink($file);
        }
    }
    rmdir($dirPath);
}

// Acción: Save product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_product') {
  $token = $_POST['csrf_token'] ?? '';
  if (!hash_equals($csrf, $token)) {
    $saveErr = "Token inválido. Recarga la página e intenta de nuevo.";
  } else {
    $original_key = trim($_POST['original_key'] ?? '');
    $key = trim($_POST['prod_key'] ?? '');
    $title = trim($_POST['prod_title'] ?? '');
    $price = normalize_price($_POST['prod_price'] ?? '0.00');
    $desc = trim($_POST['prod_desc'] ?? '');
    $icon = trim($_POST['prod_icon'] ?? '');
    if (empty($icon)) {
        $rIcons = ['fa-solid fa-file-contract', 'fa-solid fa-file-signature', 'fa-solid fa-handshake', 'fa-solid fa-folder-open', 'fa-solid fa-file-invoice', 'fa-solid fa-certificate', 'fa-solid fa-book'];
        $icon = $rIcons[array_rand($rIcons)];
    }
    $tags = trim($_POST['prod_tags'] ?? 'all');
    $url = trim($_POST['prod_url'] ?? '');
    
    // Validate key
    if (!preg_match('/^[a-z0-9_]+$/', $key)) {
        $saveErr = "La Key solo puede contener letras minúsculas, números y guiones bajos.";
    } elseif ($price === null) {
        $saveErr = "Precio inválido.";
    } elseif ($original_key !== $key && isset($catalog[$key])) {
        $saveErr = "La Key '{$key}' ya existe.";
    } else {
        // If it's an edit and key changed, we need to rename the directory
        $dirPath = __DIR__ . "/recursos/{$key}";
        if ($original_key && $original_key !== $key) {
            $oldDirPath = __DIR__ . "/recursos/{$original_key}";
            if (is_dir($oldDirPath)) {
                rename($oldDirPath, $dirPath);
            }
            // Remove old key from catalog
            unset($catalog[$original_key]);
        }
        
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0777, true);
        }

        // Previous image count
        $imgCount = 0;
        if (isset($catalog[$key])) {
            $imgCount = (int)($catalog[$key]['images'] ?? 0);
        } else if ($original_key && isset($catalog[$original_key])) {
            $imgCount = (int)($catalog[$original_key]['images'] ?? 0);
        }

        // Handle image uploads
        if (!empty($_FILES['prod_images']['name'][0])) {
            // Delete old images first to replace them all
            $files = glob($dirPath . '/*');
            foreach($files as $file){ 
                if(is_file($file)) unlink($file); 
            }
            
            $imgCount = 0;
            $countFiles = count($_FILES['prod_images']['name']);
            for ($i = 0; $i < $countFiles; $i++) {
                $tmpFilePath = $_FILES['prod_images']['tmp_name'][$i];
                if ($tmpFilePath != "") {
                    $imgCount++;
                    $newFilePath = $dirPath . "/{$imgCount}.jpg";
                    move_uploaded_file($tmpFilePath, $newFilePath);
                }
            }
        }
        
        // Save to catalog array
        $catalog[$key] = [
            'price' => $price,
            'title' => $title,
            'description' => $desc,
            'icon' => $icon,
            'tags' => $tags,
            'url' => $url,
            'images' => $imgCount
        ];
        
        $err = write_catalog_file($catalogPath, $catalog);
        if ($err) $saveErr = $err;
        else $saveOk = "Producto '{$title}' guardado correctamente.";
    }
  }
}

// Acción: Delete product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_product') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf, $token)) {
      $saveErr = "Token inválido.";
    } else {
      $key = trim($_POST['delete_key'] ?? '');
      if (isset($catalog[$key])) {
          // Removes from array
          unset($catalog[$key]);
          // Removes directory
          deleteDir(__DIR__ . "/recursos/{$key}");
          
          $err = write_catalog_file($catalogPath, $catalog);
          if ($err) $saveErr = $err;
          else $saveOk = "Producto '{$key}' eliminado correctamente.";
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
    .contracts-hero {
      margin-top: 0 !important;
    }
    :root { --nav-offset: 96px; } /* fallback */
  </style>
</head>

<body>
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
        <p class="contracts-subtitle" style="margin: 0 auto;">Gestión completa de productos y contratos dinámicos.</p>
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

        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-3">
          <div>
            <div class="h5 mb-1">Catálogo de Productos</div>
            <div class="muted">Lista de contratos a mostrar en la tienda.</div>
          </div>
          <div class="d-flex gap-2">
              <button class="btn btn-primary rounded-pill btn-add-product" data-bs-toggle="modal" data-bs-target="#productModal">
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
                <th style="min-width:200px;">Producto</th>
                <th>Key / Precio</th>
                <th>Tags & Icono</th>
                <th>Imágenes</th>
                <th class="text-end" style="width:180px;">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($catalog as $key => $item):
                $title = $item['title'] ?? $key;
                $price = $item['price'] ?? '0.00';
                $desc = $item['description'] ?? '';
                $icon = $item['icon'] ?? 'fa-solid fa-file';
                $tags = $item['tags'] ?? 'all';
                $imgs = (int)($item['images'] ?? 0);
              ?>
              <tr>
                <td>
                    <div class="fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="<?php echo htmlspecialchars($icon); ?> text-primary"></i> 
                        <?php echo htmlspecialchars($title); ?>
                    </div>
                    <div class="small text-muted text-truncate" style="max-width:250px;"><?php echo htmlspecialchars($desc); ?></div>
                </td>
                <td>
                    <div class="small text-muted"><code><?php echo htmlspecialchars($key); ?></code></div>
                    <div class="fw-bold">$<?php echo htmlspecialchars($price); ?> MXN</div>
                </td>
                <td>
                    <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($tags); ?></span>
                </td>
                <td>
                    <?php if($imgs > 0): ?>
                        <span class="badge bg-info rounded-pill"><?php echo $imgs; ?> imgs</span>
                    <?php else: ?>
                        <span class="badge bg-light text-muted border">Sin imgs</span>
                    <?php endif; ?>
                </td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-warning rounded-pill edit-product-btn"
                        data-key="<?php echo htmlspecialchars($key); ?>"
                        data-title="<?php echo htmlspecialchars($title); ?>"
                        data-price="<?php echo htmlspecialchars($price); ?>"
                        data-desc="<?php echo htmlspecialchars($desc); ?>"
                        data-icon="<?php echo htmlspecialchars($icon); ?>"
                        data-tags="<?php echo htmlspecialchars($tags); ?>"
                        data-url="<?php echo htmlspecialchars($item['url'] ?? ''); ?>"
                        data-bs-toggle="modal" data-bs-target="#productModal">
                        <i class="fas fa-edit"></i>
                    </button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('¿Seguro que deseas eliminar este producto y sus imágenes?');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                        <input type="hidden" name="action" value="delete_product">
                        <input type="hidden" name="delete_key" value="<?php echo htmlspecialchars($key); ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </td>
              </tr>
              <?php endforeach; ?>
              
              <?php if (empty($catalog)): ?>
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">El catálogo está vacío. ¡Agrega tu primer producto!</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </section>

  <!-- Product Modal -->
  <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content" style="border-radius: 20px; border:none; box-shadow: 0 15px 50px rgba(0,0,0,0.1);">
        <div class="modal-header border-bottom-0 pb-0">
          <h5 class="modal-title section-title px-2 pt-2" id="productModalLabel">Modificar Producto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
          <input type="hidden" name="action" value="save_product">
          <input type="hidden" name="original_key" id="original_key" value="">
          
          <div class="modal-body px-4 py-3">
            <div class="row g-3">
                <div class="col-md-6">
                  <label for="prod_key" class="form-label fw-bold small mb-1">Key Única del Sistema <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="prod_key" name="prod_key" placeholder="ej_mi_contrato" required pattern="[a-z0-9_]+" style="border-radius: 12px;">
                  <div class="form-text small">Solo minúsculas, números o guiones bajos. Sin espacios.</div>
                </div>
                
                <div class="col-md-6">
                  <label for="prod_price" class="form-label fw-bold small mb-1">Precio (MXN) <span class="text-danger">*</span></label>
                  <div class="input-group">
                      <span class="input-group-text" style="border-radius: 12px 0 0 12px;">$</span>
                      <input type="text" class="form-control" id="prod_price" name="prod_price" placeholder="99.00" required inputmode="decimal" style="border-radius: 0 12px 12px 0;">
                  </div>
                </div>

                <div class="col-12">
                  <label for="prod_title" class="form-label fw-bold small mb-1">Título Visual <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="prod_title" name="prod_title" required placeholder="Contrato de Arrendamiento" style="border-radius: 12px;">
                </div>
                
                <input type="hidden" id="prod_icon" name="prod_icon" value="">

                <div class="col-12">
                  <label for="prod_desc" class="form-label fw-bold small mb-1">Descripción Corta <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="prod_desc" name="prod_desc" required placeholder="Detalles de lo que incluye el contrato." style="border-radius: 12px;">
                </div>
                
                <div class="col-md-6">
                  <label for="prod_tags" class="form-label fw-bold small mb-1">Filtros (Categorías)</label>
                  <input type="text" class="form-control" id="prod_tags" name="prod_tags" value="all" placeholder="servicios all" style="border-radius: 12px;">
                  <div class="form-text small">Separados por espacio. Usa `all` siempre. Ej: `servicios all`</div>
                </div>

                <div class="col-md-6">
                  <label for="prod_url" class="form-label fw-bold small mb-1">URL de Descarga</label>
                  <input type="text" class="form-control" id="prod_url" name="prod_url" placeholder="https://..." style="border-radius: 12px;">
                  <div class="form-text small">Dejar en blanco para usar la descarga predeterminada.</div>
                </div>

                <div class="col-md-6">
                  <label for="prod_images" class="form-label fw-bold small mb-1">Imágenes de Preview Multiples .JPG</label>
                  <input type="file" class="form-control" id="prod_images" name="prod_images[]" multiple accept=".jpg,.jpeg,.png" style="border-radius: 12px;">
                  <div class="form-text small">Si subes nuevas, reemplazarán a las existentes.</div>
                </div>
            </div>
          </div>
          <div class="modal-footer border-top-0 px-4 pb-4">
            <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm"><i class="fas fa-save me-1"></i> Guardar Producto</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer>
    <div class="container">
      <div class="footer-container">
        <div class="footer-col">
          <h3>Crece Diseño</h3>
          <p>Potenciando la industria creativa a través de la formalidad legal.</p>
        </div>
        <div class="footer-col">
          <h4>Navegación</h4>
          <ul>
            <li><a href="index.php">Inicio</a></li>
            <li><a href="cursos.php">Cursos</a></li>
            <li><a href="contratos.php">Contratos</a></li>
          </ul>
        </div>
        <div class="footer-col contact-col">
          <h4>Contáctanos</h4>
          <ul>
            <li><i class="fa-solid fa-envelope"></i> admin@crecediseno.com</li>
            <li><i class="fa-solid fa-phone"></i> +52 (000) 000-0000</li>
          </ul>
        </div>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
  <script src="scripts.js"></script>
  <script>
    AOS.init({ once: true, offset: 50 });

    const nav = document.querySelector('.custom-navbar');
    const setOffset = () => {
      const h = nav ? nav.offsetHeight : 80;
      document.documentElement.style.setProperty('--nav-offset', h + 'px');
    };
    setOffset();
    window.addEventListener('resize', setOffset);

    // Modal populate logic
    document.addEventListener('DOMContentLoaded', () => {
        const modalTitle = document.getElementById('productModalLabel');
        const origKeyInp = document.getElementById('original_key');
        
        const keyInp = document.getElementById('prod_key');
        const titleInp = document.getElementById('prod_title');
        const priceInp = document.getElementById('prod_price');
        const descInp = document.getElementById('prod_desc');
        const iconInp = document.getElementById('prod_icon');
        const tagsInp = document.getElementById('prod_tags');
        const urlInp = document.getElementById('prod_url');
        const fileInp = document.getElementById('prod_images');

        document.querySelector('.btn-add-product').addEventListener('click', () => {
            modalTitle.innerText = "Agregar Nuevo Producto";
            origKeyInp.value = "";
            keyInp.value = "";
            titleInp.value = "";
            priceInp.value = "";
            descInp.value = "";
            iconInp.value = "";
            tagsInp.value = "servicios all";
            urlInp.value = "";
            fileInp.value = "";
            fileInp.required = true;
        });

        document.querySelectorAll('.edit-product-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                modalTitle.innerText = "Modificar Producto";
                origKeyInp.value = btn.getAttribute('data-key');
                keyInp.value = btn.getAttribute('data-key');
                titleInp.value = btn.getAttribute('data-title');
                priceInp.value = btn.getAttribute('data-price');
                descInp.value = btn.getAttribute('data-desc');
                iconInp.value = btn.getAttribute('data-icon');
                tagsInp.value = btn.getAttribute('data-tags');
                urlInp.value = btn.getAttribute('data-url') || '';
                fileInp.value = "";
                fileInp.required = false;
            });
        });
    });
  </script>
</body>
</html>