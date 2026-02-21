<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/admin_flag.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Crece Diseño - Catálogo de Formación</title>

  
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">

 
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

 
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  
  <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet"/>

  
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <script>
    window.isLoggedIn = <?php echo isset($_SESSION['usuario_id']) ? 'true' : 'false'; ?>;
  </script>

  <?php if(isset($_SESSION['flash_message'])): ?>
  <div class="flash-message <?php echo $_SESSION['flash_type'] ?? 'info'; ?>" role="alert">
    <div class="flash-icon">
      <?php if(($_SESSION['flash_type'] ?? '') == 'success'): ?>
        <i class="fas fa-check-circle"></i>
      <?php else: ?>
        <i class="fas fa-info-circle"></i>
      <?php endif; ?>
    </div>
    <div class="flash-content">
      <h4><?php echo ($_SESSION['flash_type'] ?? '') == 'success' ? 'Éxito' : 'Aviso'; ?></h4>
      <p><?php echo $_SESSION['flash_message']; ?></p>
    </div>
  </div>
  <?php 
    // Clear flash message after displaying
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
  endif; ?>

  <div class="welcome-overlay <?php echo isset($_SESSION['usuario_id']) ? 'hidden' : ''; ?>" id="welcomeOverlay" aria-hidden="true">
    <img class="welcome-logo" src="logo.svg" alt="Crece Diseño" />
    <h1 class="welcome-text">Bienvenidos a Crece Diseño</h1>
  </div>

  
  <div class="register-modal" id="registerModal" role="dialog" aria-modal="true" aria-labelledby="registerTitle" aria-hidden="true">
    <div class="register-content">
      <button class="register-close" id="registerClose" aria-label="Cerrar">&times;</button>
      
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
            <li class="nav-item"><a class="nav-link" href="cursos.php">Cursos</a></li>
            <li class="nav-item"><a class="nav-link" href="contratos.php">Contratos</a></li>
            <li class="nav-item"><a class="nav-link active" href="nosotros.php">Nosotros</a></li>
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
              <?php endif; ?>
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

  
  <section class="hero" id="inicio">
    <div class="banner-container"><img src="patron1.svg" alt="Patrón de fondo" /></div>
    <div class="container">
      <div class="hero-content center-block" data-aos="fade-up" data-aos-duration="800">
        <h1>Catálogo de Formación: Gestión, Innovación y Valor</h1>
        <p>
          Plataforma conceptual y digital diseñada para transformar la gestión empresarial y el desarrollo
          profesional, alineando objetivos con principios de sostenibilidad total. Funciona como mecanismo
          de comunicación de facilitación que permite medir, planificar y actuar hacia crecimiento
          competitivo y responsable.
        </p>
        <div class="d-flex gap-2 justify-content-center">
          <a href="cursos.php" class="btn btn-primary btn-cta">Ver cursos</a>
          <a href="#contacto" class="btn btn-outline-primary btn-cta-2">Contactar</a>
        </div>
      </div>
    </div>
  </section>

  
  <section class="about-section" id="nosotros">
    <div class="container">
      <div class="row g-4 team-gallery" data-aos="fade-up" data-aos-duration="800">
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="team-member h-100">
            <div class="member-image"><img src="nl.jpg" alt="Noeliz Lopez" /></div>
            <div class="member-info">
              <h4>Noeliz Lopez</h4>
              <p>Coordinadora de Proyectos</p>
            </div>
          </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="team-member h-100">
            <div class="member-image"><img src="mn.jpg" alt="Marco A. Nava" /></div>
            <div class="member-info">
              <h4>Marco A. Nava</h4>
              <p>Diseñador e Ilustrador</p>
            </div>
          </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="team-member h-100">
            <div class="member-image"><img src="gv.jpg" alt="Geronimo Vicencio" /></div>
            <div class="member-info">
              <h4>Geronimo Vicencio</h4>
              <p>Programador</p>
            </div>
          </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="team-member h-100">
            <div class="member-image"><img src="jb.jpg" alt="Josue Becerra R." /></div>
            <div class="member-info">
              <h4>Josue Becerra R.</h4>
              <p>Investigador</p>
            </div>
          </div>
        </div>
      </div>

      <div class="about-text" data-aos="fade-up" data-aos-duration="800" data-aos-delay="150">
        <h2>¿Quiénes Somos?</h2>
        <p>Somos un colectivo dedicado a fortalecer el acceso de los diseñadores egresados del CUCOSTA a oportunidades de formación especializada, enfocados en el desarrollo y perfeccionamiento de habilidades técnicas clave para su crecimiento profesional.</p>
        <p>Nuestro compromiso es apoyar a estos profesionales mediante la creación de un catálogo digital de formación con los vínculos necesarios a herramientas que faciliten la actualización constante y la ampliación de sus competencias para enfrentar las limitaciones del mercado laboral actual.</p>
        <p>Buscamos ser un puente que conecte y proporcione acceso a las necesidades reales de la industria, promoviendo así un desarrollo integral y sostenible en el ámbito del diseño.</p>

        
        <div class="text-center mt-4">
          <a href="nosotros.php" class="btn btn-lg btn-cta about-big-cta">Conoce Más Sobre Nosotros</a>
        </div>
      </div>
    </div>
  </section>

 
  <section class="contact-section" id="contacto">
    <div class="container">
      <h2 class="section-title" data-aos="fade-up" data-aos-duration="800">Contáctanos</h2>
      <div class="contact-container" data-aos="fade-up" data-aos-duration="800" data-aos-delay="150">
        <div class="contact-info">
          <h2>Estamos aquí para ayudarte</h2>
          <p>Si tienes preguntas sobre los cursos, necesitas asesoría o quieres colaborar con nosotros, no dudes en contactarnos.</p>
          <div class="contact-details">
            <div class="contact-item">
              <span class="contact-icon"><i class="fas fa-envelope"></i></span>
              <span>contacto@crecediseño.com</span>
            </div>
            <div class="contact-item">
              <span class="contact-icon"><i class="fas fa-phone"></i></span>
              <span>+52 322 123 4567</span>
            </div>
            <div class="contact-item">
              <span class="contact-icon"><i class="fas fa-map-marker-alt"></i></span>
              <span>Puerto Vallarta, Jalisco, México</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  
  <footer>
    <div class="container">
      <div class="footer-container">
        <div class="footer-col">
          <h3>Crece Diseño</h3>
          <p>Catálogo de Formación para diseñadores gráficos en Puerto Vallarta.</p>
          <div class="social-links social-centered">
            <a href="https://www.instagram.com/crece_diseno?igsh=MWRtNHlvaGs4dmt0dA==" class="social-link" target="_blank" rel="noopener" aria-label="Instagram">
              <i class="fab fa-instagram"></i>
            </a>
            <a href="https://www.tiktok.com/@mambeturouch?_r=1&_t=ZS-918cYzJJefC" class="social-link" target="_blank" rel="noopener" aria-label="TikTok">
              <i class="fab fa-tiktok"></i>
            </a>
          </div>
        </div>
        <div class="footer-col">
          <h3>Enlaces Rápidos</h3>
          <a href="index.php">Inicio</a>
          <a href="cursos.php">Cursos</a>
          <a href="contratos.php">Contratos</a>
          <a href="nosotros.php">Nosotros</a>
          <a href="index.php#contacto">Contacto</a>
        </div>
        <div class="footer-col">
          <h3>Cursos</h3>
          <a href="cursos.php#gratuitos">Cursos Gratuitos</a>
          <a href="cursos.php#paga">Cursos de Paga</a>
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
        <p>&copy; 2023 Crece Diseño. Todos los derechos reservados.</p>
      </div>
    </div>
  </footer>

  
  <a class="whatsapp-float" target="_blank" rel="noopener"
     href="https://whatsapp.com/channel/0029VbBAW0D3LdQPDltrrs1N">
    <i class="fab fa-whatsapp"></i>
  </a>

  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
  
  <script src="scripts.js"></script>
</body>
</html>
