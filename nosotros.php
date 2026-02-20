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
  <title>Nosotros - Crece Diseño</title>

  
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">

  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  
  <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet"/>

  
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  
  <div class="background-container"></div>

 
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
            <?php else: ?>
                <li class="nav-item"><a class="nav-link btn btn-primary btn-cta" href="index.php" onclick="localStorage.setItem('openModal', 'true'); window.location.href='index.php'; return false;">Registrarse / Iniciar Sesión</a></li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </nav>
  </header>

 
  <section class="hero" id="inicio">
    <div class="banner-container">
      <img src="patron1.svg" alt="Patrón de fondo" />
    </div>

    <div class="container">
      <div class="hero-content center-block" data-aos="fade-up" data-aos-duration="800">
        <h1>Conoce Nuestra Historia</h1>
        <p>
          Un colectivo comprometido con el crecimiento y desarrollo profesional 
          de los diseñadores gráficos en Puerto Vallarta
        </p>
      </div>
    </div>
  </section>

 
  <section class="about-section" id="quienes-somos">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-6" data-aos="fade-right" data-aos-duration="800">
          <div class="about-text">
            <h2>¿Quiénes Somos?</h2>
            <p>Somos un colectivo dedicado a fortalecer el acceso de los diseñadores egresados del CUCOSTA a oportunidades de formación especializada, enfocados en el desarrollo y perfeccionamiento de habilidades técnicas clave para su crecimiento profesional.</p>
            <p>Nuestro compromiso es apoyar a estos profesionales mediante la creación de un catálogo digital de formación con los vínculos necesarios a herramientas que faciliten la actualización constante y la ampliación de sus competencias para enfrentar las limitaciones del mercado laboral actual.</p>
            <p>Buscamos ser un puente que conecte y proporcione acceso a las necesidades reales de la industria, promoviendo así un desarrollo integral y sostenible en el ámbito del diseño.</p>
          </div>
        </div>
        <div class="col-lg-6" data-aos="fade-left" data-aos-duration="800" data-aos-delay="200">
          <div class="about-image">
            <img src="ramita.svg" alt="Equipo Crece Diseño" class="img-fluid rounded-3 shadow" />
          </div>
        </div>
      </div>
    </div>
  </section>

  
  <section class="catalog-section py-5 bg-light">
    <div class="container">
      <div class="row">
        <div class="col-12 text-center mb-5" data-aos="fade-up" data-aos-duration="800">
          <h2 class="section-title">Catálogo de Vinculación</h2>
          <p class="lead">Conectando talento con oportunidades</p>
        </div>
      </div>
      <div class="row g-4">
        <div class="col-md-6" data-aos="fade-up" data-aos-duration="800">
          <div class="catalog-card p-4 h-100 bg-white rounded-3 shadow-sm">
            <div class="catalog-icon mb-3">
              <i class="fas fa-book-open fa-2x text-primary"></i>
            </div>
            <h4>Formación Continua</h4>
            <p>Es un catálogo cuentero que aprovecha oportunidades organizadas de noviembre profesional diferencial durante de acuerdo a la formación.</p>
          </div>
        </div>
        <div class="col-md-6" data-aos="fade-up" data-aos-duration="800" data-aos-delay="100">
          <div class="catalog-card p-4 h-100 bg-white rounded-3 shadow-sm">
            <div class="catalog-icon mb-3">
              <i class="fas fa-network-wired fa-2x text-primary"></i>
            </div>
            <h4>Vinculación Institucional</h4>
            <p>Constable por la "Institut actual" (vinculación institucional) mediante la creación de recursos 1-2 y nombres y distribuidas.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  
  <section class="mission-vision-section py-5">
    <div class="container">
      <div class="row g-5">
        <div class="col-lg-6" data-aos="fade-right" data-aos-duration="800">
          <div class="mission-card p-4 h-100 bg-white rounded-3 shadow-sm">
            <div class="mission-icon mb-3">
              <i class="fas fa-bullseye fa-2x text-primary"></i>
            </div>
            <h3 class="text-center mb-4">Misión</h3>
            <p class="text-center">Facilitar y ampliar el acceso a la actualización constante de habilidades técnicas y conocimientos para los diseñadores de Puerto Vallarta, superando las barreras que limitan su desarrollo profesional y promoviendo su crecimiento y actualización continua en un entorno dinámico y competitivo.</p>
          </div>
        </div>
        <div class="col-lg-6" data-aos="fade-left" data-aos-duration="800" data-aos-delay="200">
          <div class="vision-card p-4 h-100 bg-white rounded-3 shadow-sm">
            <div class="vision-icon mb-3">
              <i class="fas fa-eye fa-2x text-primary"></i>
            </div>
            <h3 class="text-center mb-4">Visión</h3>
            <p class="text-center">Ser el principal catálogo de relevancia para los diseñadores gráficos en Puerto Vallarta, facilitando el acceso, rehabilitación y directo a cursos y oportunidades formativas para potenciarlos. También fortaleceremos a los profesionales del diseño, fortaleciendo así su crecimiento y la innovación en la construcción científica local.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  
  <section class="founders-section py-5 bg-light">
    <div class="container">
      <div class="row">
        <div class="col-12 text-center mb-5" data-aos="fade-up" data-aos-duration="800">
          <h2 class="section-title">Nuestro Equipo Fundador</h2>
          <p class="lead">Conoce a las mentes detrás de Crece Diseño</p>
        </div>
      </div>
      <div class="row g-4 team-gallery">
        <div class="col-12 col-sm-6 col-lg-3" data-aos="fade-up" data-aos-duration="800">
          <div class="team-member h-100 text-center">
            <div class="member-image mx-auto">
              <img src="nl.jpg" alt="Noeliz Lopez" />
            </div>
            <div class="member-info">
              <h4>Noeliz Lopez</h4>
              <p class="text-muted">Coordinadora de Proyectos</p>
              <p class="small">Lidera la estrategia y coordinación de todos nuestros proyectos formativos.</p>
            </div>
          </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3" data-aos="fade-up" data-aos-duration="800" data-aos-delay="100">
          <div class="team-member h-100 text-center">
            <div class="member-image mx-auto">
              <img src="mn.jpg" alt="Marco A. Nava" />
            </div>
            <div class="member-info">
              <h4>Marco A. Nava</h4>
              <p class="text-muted">Diseñador e Ilustrador</p>
              <p class="small">Responsable de la identidad visual y experiencia de usuario de nuestra plataforma.</p>
            </div>
          </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3" data-aos="fade-up" data-aos-duration="800" data-aos-delay="200">
          <div class="team-member h-100 text-center">
            <div class="member-image mx-auto">
              <img src="gv.jpg" alt="Geronimo Vicencio" />
            </div>
            <div class="member-info">
              <h4>Geronimo Vicencio</h4>
              <p class="text-muted">Programador</p>
              <p class="small">Desarrolla y mantiene nuestra plataforma digital y herramientas tecnológicas.</p>
            </div>
          </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3" data-aos="fade-up" data-aos-duration="800" data-aos-delay="300">
          <div class="team-member h-100 text-center">
            <div class="member-image mx-auto">
              <img src="jb.jpg" alt="Josue Becerra R." />
            </div>
            <div class="member-info">
              <h4>Josue Becerra R.</h4>
              <p class="text-muted">Investigador</p>
              <p class="small">Analiza las tendencias del mercado y necesidades de formación del sector diseño.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  
  <section class="values-section py-5">
    <div class="container">
      <div class="row">
        <div class="col-12 text-center mb-5" data-aos="fade-up" data-aos-duration="800">
          <h2 class="section-title">Nuestros Valores</h2>
        </div>
      </div>
      <div class="row g-4">
        <div class="col-md-4" data-aos="fade-up" data-aos-duration="800">
          <div class="value-card text-center p-4">
            <div class="value-icon mb-3">
              <i class="fas fa-hands-helping fa-3x text-primary"></i>
            </div>
            <h4>Colaboración</h4>
            <p>Trabajamos en equipo para crear soluciones que beneficien a toda la comunidad de diseñadores.</p>
          </div>
        </div>
        <div class="col-md-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="100">
          <div class="value-card text-center p-4">
            <div class="value-icon mb-3">
              <i class="fas fa-graduation-cap fa-3x text-primary"></i>
            </div>
            <h4>Educación Continua</h4>
            <p>Creemos en el aprendizaje constante como motor del crecimiento profesional.</p>
          </div>
        </div>
        <div class="col-md-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="200">
          <div class="value-card text-center p-4">
            <div class="value-icon mb-3">
              <i class="fas fa-rocket fa-3x text-primary"></i>
            </div>
            <h4>Innovación</h4>
            <p>Buscamos constantemente nuevas formas de conectar talento con oportunidades.</p>
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
          <p>Catálogo de formación para diseñadores gráficos en Puerto Vallarta</p>
          <div class="social-links social-centered">
            <a href="https://www.tiktok.com/@mambeturouch?_r=1&_t=ZS-918cYzJJefC" class="social-link" target="_blank" rel="noopener" aria-label="Instagram">
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
          <h3>Recursos</h3>
          <a href="cursos.php#gratuitos">Cursos Gratuitos</a>
          <a href="cursos.php#paga">Cursos de Paga</a>
          <a href="cursos.php#financiamiento">Financiamiento</a>
          <a href="#">Política de Privacidad</a>
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

  
  <script>
    window.isLoggedIn = <?php echo isset($_SESSION['usuario_id']) ? 'true' : 'false'; ?>;
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
 >
  <script src="scripts.js"></script>
</body>
</html>