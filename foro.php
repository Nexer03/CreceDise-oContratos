<?php
session_start();
$nombre_usuario = $_SESSION['usuario_nombre'] ?? 'Usuario';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foro de Comunicación - Crece Diseño</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet"/>

    <link rel="stylesheet" href="../styles.css">
    
</head>
<body>
   
    <div class="background-container"></div> 

  
   <header class="shadow-sm">
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
            <li class="nav-item"><a class="nav-link" href="foro.php">Foro</a></li>
            
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

    <!-- Hero Section -->
    <section class="hero" id="inicio">
        <div class="banner-container"><img src="../recursos/patron1.svg" alt="Banner Patron"></div>
        <div class="container">
            <div class="hero-content">
                <h1>Foro de Comunicación</h1>
                <p>Espacio para compartir información, anuncios y comunicaciones importantes sobre nuestros cursos y actividades.</p>
            </div>
        </div>
    </section>

    <!-- Foro Section -->
    <section class="about-section" id="foro">
        <div class="container">
            <h2 class="section-title">Comunicaciones y Anuncios</h2>
            <div class="foro-container">
                <div class="foro-header">
                    <h2>Foro de la Clase</h2>
                </div>
                <div class="foro-body" id="foroBody">
                  
                    <div class="empty-foro">
                        <i class="fas fa-comments"></i>
                        <h3>Foro vacío</h3>
                        <p>No hay mensajes todavía. ¡Sé el primero en publicar!</p>
                    </div>
                </div>
                
                <div class="add-comment">
                    <h3>Agregar un comentario de clase</h3>
                    <form class="comment-form" id="commentForm">
                        <div class="form-group">
                            <label for="authorName">Tu nombre</label>
                            <input 
                            type="text" 
                            id="authorName" 
                            class="form-control" 
                            value="<?php echo htmlspecialchars($nombre_usuario, ENT_QUOTES, 'UTF-8'); ?>" 
                            readonly
                            >
                        </div>
                        <div class="form-group">
                            <label for="commentContent">Tu mensaje</label>
                            <textarea id="commentContent" class="form-control" placeholder="Escribe tu mensaje aquí..." required></textarea>
                        </div>
                        <button type="submit" class="btn">Publicar Comentario</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

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
                <div class="footer-col">
                    <h3>Enlaces Rápidos</h3>
                    <a href="../index.html">Inicio</a>
                    <a href="cursos.html">Cursos</a>
                    <a href="../index.html#nosotros">Nosotros</a>
                    <a href="foro.html">Foro</a>
                    <a href="../html/usuario.html">Mi Perfil</a>
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

    <!-- WhatsApp Float Button -->
    <a href="https://wa.me/523221234567?text=Hola,%20me%20interesa%20saber%20más%20sobre%20sus%20cursos" class="whatsapp-float" target="_blank">
        <i class="fab fa-whatsapp"></i>
    </a>
    <script>
    document.addEventListener('DOMContentLoaded', function(){
      const menuBtn  = document.querySelector('.mobile-menu-btn');
      const mainNav  = document.getElementById('mainNav');

      if(menuBtn && mainNav){
        menuBtn.addEventListener('click', function(){
          const open = mainNav.classList.toggle('show');
          menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

      
        mainNav.querySelectorAll('a').forEach(a=>{
          a.addEventListener('click', ()=> mainNav.classList.remove('show'));
        });
      }
    });

  
    (function(){
      const _initSmoothScroll = initSmoothScroll;
      window.initSmoothScroll = function(){
        _initSmoothScroll();
        const mainNav = document.getElementById('mainNav');
        document.querySelectorAll('a[href^="#"]').forEach(anchor=>{
          anchor.addEventListener('click', function(){
            if(mainNav && mainNav.classList.contains('show')){
              mainNav.classList.remove('show');
            }
          });
        });
      };
    })();
    </script>
    <script>
        const foroBody  = document.getElementById('foroBody');
        const PAGE_SIZE = 10;
        let offset      = 0;
        let fin         = false;
        let cargando    = false;

        function renderRespuesta(item) {
        const r = document.createElement('div');
        r.className = 'reply';
        r.style.margin = '10px 0 0 0';
        r.style.padding = '12px 14px';
        r.style.background = 'var(--light-bg)';
        r.style.borderRadius = '8px';

        const head = document.createElement('div');
        head.style.display = 'flex';
        head.style.justifyContent = 'space-between';
        head.style.marginBottom = '6px';

        const a = document.createElement('strong');
        a.textContent = item.nombre_usuario; 

        const f = document.createElement('span');
        f.style.fontSize = '0.85rem';
        f.style.color = 'var(--dark-gray)';
        f.textContent = new Date(item.creado_en).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' });

        head.appendChild(a);
        head.appendChild(f);

        const c = document.createElement('div');
        c.textContent = item.contenido;

        r.appendChild(head);
        r.appendChild(c);
        return r;
        }

        async function cargarRespuestas(mensajeId, contenedor) {
        try {
            const res = await fetch(`../config/foro_listar_respuestas.php?mensaje_id=${mensajeId}`);
            const data = await res.json();
            if (!data.ok) return;
            contenedor.innerHTML = '';
            data.items.forEach(it => contenedor.appendChild(renderRespuesta(it)));
        } catch (e) {
            console.error('Error al cargar respuestas:', e);
        }
        }

        function renderMensaje(item) {
        const wrap = document.createElement('div');
        wrap.className = 'message';

        const header = document.createElement('div');
        header.className = 'message-header';

        const autor = document.createElement('span');
        autor.className = 'message-author';
        autor.textContent = item.nombre_usuario;


        const fecha = document.createElement('span');
        fecha.className = 'message-date';
        fecha.textContent = new Date(item.creado_en).toLocaleString('es-MX', {
            dateStyle: 'medium',
            timeStyle: 'short'
        });

        header.appendChild(autor);
        header.appendChild(fecha);

        const contenido = document.createElement('div');
        contenido.className = 'message-content';
        contenido.textContent = item.contenido;

        const repliesBox = document.createElement('div');
        repliesBox.className = 'replies';
        repliesBox.style.marginTop = '10px';
        repliesBox.style.paddingLeft = '10px';
        repliesBox.style.borderLeft = '3px solid var(--medium-gray)';

        const replyToggle = document.createElement('button');
        replyToggle.type = 'button';
        replyToggle.className = 'btn_reply';
        replyToggle.style.marginTop = '10px';
        replyToggle.textContent = 'Responder';

        const replyForm = document.createElement('form');
        replyForm.style.display = 'none';
        replyForm.style.marginTop = '10px';

    
        replyForm.innerHTML = `
            <div class="form-group">
            <label>Tu nombre</label>
            <input type="text" name="autor" class="form-control" 
                    value="<?php echo htmlspecialchars($nombre_usuario, ENT_QUOTES, 'UTF-8'); ?>" readonly>
            </div>
            <div class="form-group">
            <label>Tu respuesta</label>
            <textarea name="contenido" class="form-control" required></textarea>
            </div>
            <button type="submit" class="btn">Publicar respuesta</button>
        `;

        replyToggle.addEventListener('click', () => {
            replyForm.style.display = replyForm.style.display === 'none' ? 'block' : 'none';
        });

        replyForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const contenido = replyForm.querySelector('textarea[name="contenido"]').value.trim();
            if (contenido.length < 3) {
            alert('Escribe una respuesta válida.');
            return;
            }

            const fd = new FormData();
            fd.append('mensaje_id', item.id);
            fd.append('contenido', contenido); 

            try {
            const res = await fetch('../config/foro_respuestas.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.ok) {
                repliesBox.appendChild(renderRespuesta(data));
                replyForm.reset();
                replyForm.style.display = 'none';
            } else {
                alert('No se pudo guardar la respuesta.');
            }
            } catch (_) {
            alert('Error al enviar la respuesta.');
            }
        });

        wrap.appendChild(header);
        wrap.appendChild(contenido);
        wrap.appendChild(repliesBox);
        wrap.appendChild(replyToggle);
        wrap.appendChild(replyForm);

        cargarRespuestas(item.id, repliesBox);
        return wrap;
        }

        async function cargarMensajes() {
        if (cargando || fin) return;
        cargando = true;
        try {
            const res = await fetch(`../config/foro_listar.php?limit=${PAGE_SIZE}&offset=${offset}`);
            const data = await res.json();
            if (!data.ok) return;

            const empty = document.querySelector('.empty-foro');
            if (data.items.length && empty) empty.remove();

            data.items.forEach(item => foroBody.appendChild(renderMensaje(item)));
            offset += data.items.length;
            if (data.items.length < PAGE_SIZE) fin = true;
        } finally {
            cargando = false;
        }
        }

        document.addEventListener('DOMContentLoaded', cargarMensajes);

       
        document.getElementById('commentForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const commentContent = document.getElementById('commentContent').value.trim();

        if (commentContent.length < 3) {
            alert('Por favor, escribe un mensaje válido.');
            return;
        }

        const fd = new FormData();
        fd.append('contenido', commentContent); 

        try {
            const res = await fetch('../config/foro_guardar.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.ok) {
            const empty = document.querySelector('.empty-foro');
            if (empty) empty.remove();
            foroBody.prepend(renderMensaje(data));
            this.reset();
            } else {
            alert(data.msg || 'No se pudo guardar el mensaje.');
            }
        } catch (_) {
            alert('Error al enviar el mensaje.');
        }
        });
        </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="/scripts.js"></script>

</body>
</html>