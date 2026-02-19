<?php session_start();

require_once __DIR__ . '/config/config.php';

?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Crece Diseño - Contratos</title>

  <!-- Tipografías -->
  <link
    href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&display=swap"
    rel="stylesheet">

  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- AOS (animaciones) -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet" />

  <!-- CSS principal -->
  <link rel="stylesheet" href="styles.css" />
  <!-- CSS de esta página -->
  <link rel="stylesheet" href="contratos.css" />
  <!-- PayPal JS SDK (Buttons). Hosted Buttons (BAA...) NO sirve con /api/create-order.php y /api/capture-order.php -->
  <script src="https://www.paypal.com/sdk/js?client-id=<?php echo htmlspecialchars(PAYPAL_CLIENT_ID, ENT_QUOTES, 'UTF-8'); ?>&currency=MXN&intent=capture&disable-funding=venmo"></script>
</head>

<body>

  <!-- Fondo (igual que index) -->
  <div class="background-container"></div>

  <!-- NAVBAR (MISMO del index) -->
  <header class="shadow-sm">
    <nav class="navbar navbar-expand-lg bg-white-95 fixed-top custom-navbar" id="mainNavbar">
      <div class="container-fluid px-2 px-sm-3 px-lg-4">

        <a class="navbar-brand d-flex align-items-center me-auto brand-left" href="index.html">
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
            <li class="nav-item"><a class="nav-link active" href="contratos.php">Contratos</a></li>
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
        <h1>Contratos editables</h1>
        <p>
          Abre el contrato, llena los campos editables y descarga el PDF limpio (sin líneas).
        </p>

        <div class="contracts-search" data-aos="fade-up" data-aos-delay="150">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input id="contractSearch" type="text" placeholder="Buscar contrato..." aria-label="Buscar contrato">
        </div>

        <div class="contracts-chips" data-aos="fade-up" data-aos-delay="250">
          <button class="chip active" data-filter="all">Todos</button>
          <button class="chip" data-filter="servicios">Servicios</button>
          <button class="chip" data-filter="propiedad">Propiedad intelectual</button>
          <button class="chip" data-filter="operacion">Operación</button>
        </div>
      </div>
    </div>
  </section>

  <!-- LISTADO DE CONTRATOS -->
  <section class="contracts-list">
    <div class="container">

      <div class="row g-4" id="contractsGrid">

        <!-- Prestación de servicios -->
        <div class="col-12 col-md-6 col-lg-4 contract-item" data-tags="servicios all"
          data-title="Prestación de Servicios">
          <div class="contract-card h-100" data-aos="fade-up" data-aos-duration="800">
            <div class="contract-card-header">
              <div class="contract-icon"><i class="fa-solid fa-file-signature"></i></div>
              <div>
                <h3>Prestación de Servicios</h3>
                <p>Contrato legal editable + PDF limpio.</p>
              </div>
            </div>

            <?php if(isset($_SESSION['usuario_id'])): ?>
            <!-- No tocamos tu diseño: solo cambiamos el render a PayPal Buttons + endpoints -->
            <div id="paypal-container-U9PJN3SUK4VTG" class="paypal-btn" data-product="prestacion_servicios"></div>
            <?php else: ?>
            <button class="btn btn-primary btn-cta w-100" onclick="showRegisterModal()">
              Iniciar Sesión para Comprar
            </button>
            <?php endif; ?>


          </div>
        </div>

        <!-- Entrega Express -->
        <div class="col-12 col-md-6 col-lg-4 contract-item" data-tags="operacion all" data-title="Entrega Express">
          <div class="contract-card h-100" data-aos="fade-up" data-aos-duration="800" data-aos-delay="50">
            <div class="contract-card-header">
              <div class="contract-icon"><i class="fa-solid fa-truck-fast"></i></div>
              <div>
                <h3>Entrega Express</h3>
                <p>Condiciones de entrega, tiempos y penalizaciones.</p>
              </div>
            </div>

            <div class="contract-meta">

            </div>

            <?php if(isset($_SESSION['usuario_id'])): ?>
            <div id="paypal-container-4AQDTQTL4GPJ4" class="paypal-btn" data-product="entrega_express"></div>
            <?php else: ?>
            <button class="btn btn-primary btn-cta w-100" onclick="showRegisterModal()">
              Iniciar Sesión para Comprar
            </button>
            <?php endif; ?>
          </div>
        </div>

        <!-- Licencia Temporal -->
        <div class="col-12 col-md-6 col-lg-4 contract-item" data-tags="propiedad all" data-title="Licencia Temporal">
          <div class="contract-card h-100" data-aos="fade-up" data-aos-duration="800" data-aos-delay="100">
            <div class="contract-card-header">
              <div class="contract-icon"><i class="fa-solid fa-clock"></i></div>
              <div>
                <h3>Licencia Temporal</h3>
                <p>Uso por tiempo definido, territorio y restricciones.</p>
              </div>
            </div>


            <?php if(isset($_SESSION['usuario_id'])): ?>
            <div id="paypal-container-GRLEAVMGX7VUA" class="paypal-btn" data-product="licencia_temporal"></div>
            <?php else: ?>
            <button class="btn btn-primary btn-cta w-100" onclick="showRegisterModal()">
              Iniciar Sesión para Comprar
            </button>
            <?php endif; ?>
          </div>
        </div>

        <!-- Branding -->
        <div class="col-12 col-md-6 col-lg-4 contract-item" data-tags="servicios all"
          data-title="Branding y Diseño Gráfico">
          <div class="contract-card h-100" data-aos="fade-up" data-aos-duration="800">
            <div class="contract-card-header">
              <div class="contract-icon"><i class="fa-solid fa-pen-nib"></i></div>
              <div>
                <h3>Branding y Diseño</h3>
                <p>Alcance, entregables y propiedad intelectual.</p>
              </div>
            </div>

            <?php if(isset($_SESSION['usuario_id'])): ?>
            <div id="paypal-container-F3Y4CE6RFLNV4" class="paypal-btn" data-product="branding_diseno"></div>
            <?php else: ?>
            <button class="btn btn-primary btn-cta w-100" onclick="showRegisterModal()">
              Iniciar Sesión para Comprar
            </button>
            <?php endif; ?>
          </div>
        </div>

        <!-- Freelance -->
        <div class="col-12 col-md-6 col-lg-4 contract-item" data-tags="servicios all" data-title="Freelance">
          <div class="contract-card h-100" data-aos="fade-up" data-aos-duration="800" data-aos-delay="50">
            <div class="contract-card-header">
              <div class="contract-icon"><i class="fa-solid fa-user-tie"></i></div>
              <div>
                <h3>Freelance</h3>
                <p>Servicios independientes, pagos y entregas.</p>
              </div>
            </div>

            <div class="contract-meta">
              <span class="pill">Editable</span>
              <span class="pill">PDF</span>
            </div>

            <div class="contract-actions">
            <?php if(isset($_SESSION['usuario_id'])): ?>
              <div class="paypal-wrap">
                <div class="paypal-btn" data-product="freelance"></div>
              </div>
            <?php else: ?>
              <button class="btn btn-primary btn-cta w-100" onclick="showRegisterModal()">
                Iniciar Sesión para Comprar
              </button>
            <?php endif; ?>
          </div>

          </div>
        </div>

        <!-- Colaboración -->
        <div class="col-12 col-md-6 col-lg-4 contract-item" data-tags="operacion all" data-title="Colaboración">
          <div class="contract-card h-100" data-aos="fade-up" data-aos-duration="800" data-aos-delay="100">
            <div class="contract-card-header">
              <div class="contract-icon"><i class="fa-solid fa-people-group"></i></div>
              <div>
                <h3>Colaboración</h3>
                <p>Acuerdo de colaboración con entregables.</p>
              </div>
            </div>

            <div class="contract-meta">
              <span class="pill">Editable</span>
              <span class="pill">PDF</span>
            </div>

            <div class="contract-actions">
              <?php if(isset($_SESSION['usuario_id'])): ?>
                <div class="paypal-wrap">
                  <div class="paypal-btn" data-product="colaboracion"></div>
                </div>
              <?php else: ?>
                <button class="btn btn-primary btn-cta w-100" onclick="showRegisterModal()">
                  Iniciar Sesión para Comprar
                </button>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Obra por encargo -->
        <div class="col-12 col-md-6 col-lg-4 contract-item" data-tags="propiedad all" data-title="Obra por Encargo">
          <div class="contract-card h-100" data-aos="fade-up" data-aos-duration="800">
            <div class="contract-card-header">
              <div class="contract-icon"><i class="fa-solid fa-copyright"></i></div>
              <div>
                <h3>Obra por Encargo</h3>
                <p>Work for hire, entregables y cesión patrimonial.</p>
              </div>
            </div>

            <div class="contract-meta">
              <span class="pill">Editable</span>
              <span class="pill">PDF</span>
            </div>

            <div class="contract-actions">
              <?php if(isset($_SESSION['usuario_id'])): ?>
                <div class="paypal-wrap">
                  <div class="paypal-btn" data-product="obra_por_encargo"></div>
                </div>
              <?php else: ?>
                <button class="btn btn-primary btn-cta w-100" onclick="showRegisterModal()">
                  Iniciar Sesión para Comprar
                </button>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Cesión de derechos -->
        <div class="col-12 col-md-6 col-lg-4 contract-item" data-tags="propiedad all" data-title="Cesión de Derechos">
          <div class="contract-card h-100" data-aos="fade-up" data-aos-duration="800" data-aos-delay="50">
            <div class="contract-card-header">
              <div class="contract-icon"><i class="fa-solid fa-file-contract"></i></div>
              <div>
                <h3>Cesión de Derechos</h3>
                <p>Cesión patrimonial, territorio y vigencia.</p>
              </div>
            </div>

            <div class="contract-meta">
              <span class="pill">Editable</span>
              <span class="pill">PDF</span>
            </div>

            <div class="contract-actions">
              <?php if(isset($_SESSION['usuario_id'])): ?>
                <div class="paypal-wrap">
                  <div class="paypal-btn" data-product="cesion_derechos"></div>
                </div>
              <?php else: ?>
                <button class="btn btn-primary btn-cta w-100" onclick="showRegisterModal()">
                  Iniciar Sesión para Comprar
                </button>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Terminación -->
        <div class="col-12 col-md-6 col-lg-4 contract-item" data-tags="operacion all"
          data-title="Terminación Anticipada">
          <div class="contract-card h-100" data-aos="fade-up" data-aos-duration="800" data-aos-delay="100">
            <div class="contract-card-header">
              <div class="contract-icon"><i class="fa-solid fa-ban"></i></div>
              <div>
                <h3>Terminación Anticipada</h3>
                <p>Convenio de terminación y finiquito.</p>
              </div>
            </div>

            <div class="contract-meta">
              <span class="pill">Editable</span>
              <span class="pill">PDF</span>
            </div>

            <div class="contract-actions">
              <?php if(isset($_SESSION['usuario_id'])): ?>
                <div class="paypal-wrap">
                  <div class="paypal-btn" data-product="terminacion_anticipada"></div>
                </div>
              <?php else: ?>
                <button class="btn btn-primary btn-cta w-100" onclick="showRegisterModal()">
                  Iniciar Sesión para Comprar
                </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <div class="contracts-empty" id="contractsEmpty" hidden>
        <p>No se encontraron contratos con ese criterio.</p>
      </div>

    </div>
  </section>

  <!-- Footer (ligero, consistente) -->
  <footer class="mt-5">
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

  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>

  <script>
    // AOS como tu web
    AOS.init({ duration: 800, once: true, offset: 100 });

    // Efecto navbar al hacer scroll (igual lógica que scripts.js pero sin modal)
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

    // Buscar y filtrar
    const search = document.getElementById('contractSearch');
    const items = Array.from(document.querySelectorAll('.contract-item'));
    const empty = document.getElementById('contractsEmpty');
    const chips = Array.from(document.querySelectorAll('.chip'));

    let activeFilter = 'all';

    function applyFilters() {
      const q = (search.value || '').trim().toLowerCase();
      let visible = 0;

      items.forEach(card => {
        const title = (card.dataset.title || '').toLowerCase();
        const tags = (card.dataset.tags || '').toLowerCase();

        const matchesText = !q || title.includes(q);
        const matchesTag = activeFilter === 'all' || tags.includes(activeFilter);

        const show = matchesText && matchesTag;
        card.style.display = show ? '' : 'none';
        if (show) visible++;
      });

      empty.hidden = visible !== 0;
    }

    chips.forEach(btn => {
      btn.addEventListener('click', () => {
        chips.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        activeFilter = btn.dataset.filter || 'all';
        applyFilters();
      });
    });

    search.addEventListener('input', applyFilters);

    // --- PayPal Buttons (usa tus endpoints) ---
    // No tocamos tu UI: renderizamos dentro de los mismos contenedores.
    (function initPayPalButtons(){
      if (!window.paypal) return;

      const renderOne = (el) => {
        const product = el.dataset.product;
        if (!product) return;

        paypal.Buttons({
          createOrder: () => {
            return fetch('/api/create-order.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ product })
            })
            .then(r => r.json())
            .then(d => {
              if (!d || !d.id) throw new Error(d?.error || 'No order id');
              return d.id;
            });
          },
          onApprove: (data) => {
            return fetch('/api/capture-order.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ orderID: data.orderID, product })
            })
            .then(r => r.json())
            .then(d => {
              // Tu capture puede regresar el JSON completo de PayPal o {ok:true}
              const ok = d?.ok === true || d?.status === 'COMPLETED';
              if (!ok) throw new Error(d?.error || 'Pago no validado');
              alert('Pago completado (Sandbox)');
            });
          },
          onError: (err) => {
            console.error('PayPal error:', err);
            alert('Error en el pago. Revisa consola (F12).');
          }
        }).render(el);
      };

      document.querySelectorAll('.paypal-btn').forEach(renderOne);
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