
AOS.init({
  duration: 800,
  once: true,
  offset: 100
});


let lastScrollTop = 0;
let ticking = false;


function updateHeader() {
  const navbar = document.getElementById('mainNavbar');
  const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

  if (scrollTop > 100) {
    navbar.classList.add('navbar-scrolled');
  } else {
    navbar.classList.remove('navbar-scrolled');
  }


  if (scrollTop > lastScrollTop && scrollTop > 100) {

    navbar.classList.add('navbar-hidden');
  } else {

    navbar.classList.remove('navbar-hidden');
  }

  lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
  ticking = false;
}

function requestTick() {
  if (!ticking) {
    requestAnimationFrame(updateHeader);
    ticking = true;
  }
}


function showWelcomeOverlay() {
  const overlay = document.getElementById('welcomeOverlay');
  if (!overlay) return;
  overlay.classList.remove('hidden');


  setTimeout(() => {
    overlay.classList.add('hidden');

    setTimeout(showRegisterModal, 500);
  }, 1000);
}


function showRegisterModal() {
  const modal = document.getElementById('registerModal');
  modal.classList.add('active');


  localStorage.setItem('registerModalShown', 'true');
}

function closeRegisterModal() {
  const modal = document.getElementById('registerModal');
  modal.classList.remove('active');
}


function validateForm(form) {
  const email = form.querySelector('#email');
  const phone = form.querySelector('#phone');
  const city = form.querySelector('#city');
  let isValid = true;


  form.querySelectorAll('.error-message').forEach(el => el.remove());
  form.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid'));


  if (!email.value || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
    showError(email, 'Por favor ingresa un correo electrónico válido');
    isValid = false;
  }


  if (!phone.value || phone.value.trim().length < 10) {
    showError(phone, 'Por favor ingresa un número telefónico válido');
    isValid = false;
  }


  if (!city.value || city.value.trim().length < 3) {
    showError(city, 'Por favor ingresa tu ciudad');
    isValid = false;
  }

  return isValid;
}

function showError(input, message) {
  input.classList.add('is-invalid');
  const error = document.createElement('div');
  error.className = 'error-message text-danger mt-1 small';
  error.textContent = message;
  input.parentNode.appendChild(error);
}


function submitForm(form) {
  if (!validateForm(form)) return false;

  const formData = {
    email: form.querySelector('#email').value,
    phone: form.querySelector('#phone').value,
    city: form.querySelector('#city').value,
    timestamp: new Date().toISOString()
  };


  console.log('Datos del formulario:', formData);


  const submitBtn = form.querySelector('.register-btn');
  const originalText = submitBtn.textContent;

  submitBtn.innerHTML = '<i class="fas fa-check me-2"></i>Registro Exitoso';
  submitBtn.disabled = true;


  setTimeout(() => {
    closeRegisterModal();

    setTimeout(() => {
      submitBtn.textContent = originalText;
      submitBtn.disabled = false;
    }, 1000);
  }, 2000);

  return false;
}


function initSmoothScroll() {
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      const targetId = this.getAttribute('href');

      if (targetId === '#') return;

      const targetElement = document.querySelector(targetId);

      if (targetElement) {
        e.preventDefault();


        const navbarToggler = document.querySelector('.navbar-toggler');
        const navbarCollapse = document.querySelector('.navbar-collapse');

        if (navbarCollapse && navbarCollapse.classList.contains('show')) {
          navbarToggler.click();
        }


        const headerHeight = document.querySelector('.custom-navbar').offsetHeight;
        const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - headerHeight;

        window.scrollTo({
          top: targetPosition,
          behavior: 'smooth'
        });


        setTimeout(() => {
          setActiveNavLink();
        }, 800);
      }
    });
  });
}


function setActiveNavLink() {
  const currentSection = getCurrentSection();
  const navLinks = document.querySelectorAll('.navbar-nav .nav-link');

  navLinks.forEach(link => {
    link.classList.remove('active');
    const href = link.getAttribute('href');

    if (href === `#${currentSection}` ||
      (currentSection === 'inicio' && (href === '#inicio' || href === 'index.html' || href === '/')) ||
      (href.includes('cursos.html') && currentSection === 'cursos')) {
      link.classList.add('active');
    }
  });
}

function getCurrentSection() {
  const sections = document.querySelectorAll('section[id]');
  let currentSection = 'inicio';
  const scrollPosition = window.pageYOffset + 150;

  sections.forEach(section => {
    const sectionTop = section.offsetTop;
    const sectionHeight = section.clientHeight;
    const sectionId = section.getAttribute('id');

    if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
      currentSection = sectionId;
    }
  });

  return currentSection;
}


document.addEventListener('DOMContentLoaded', function () {

  window.addEventListener('scroll', requestTick, { passive: true });


  window.addEventListener('scroll', setActiveNavLink);

  window.addEventListener('scroll', setActiveNavLink);

  if (!window.isLoggedIn) {
    showWelcomeOverlay();
  }


  document.getElementById('registerClose').addEventListener('click', closeRegisterModal);


  document.getElementById('registerModal').addEventListener('click', function (e) {
    if (e.target === this) {
      closeRegisterModal();
    }
  });


  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeRegisterModal();
    }
  });



  initSmoothScroll();


  setActiveNavLink();


  document.querySelectorAll('.team-member').forEach(member => {
    member.addEventListener('mouseenter', function () {
      this.style.transform = 'translateY(-5px)';
    });

    member.addEventListener('mouseleave', function () {
      this.style.transform = 'translateY(0)';
    });
  });



  const navbarToggler = document.querySelector('.navbar-toggler');
  if (navbarToggler) {
    navbarToggler.addEventListener('click', function () {
      const expanded = this.getAttribute('aria-expanded') === 'true';
      this.setAttribute('aria-expanded', !expanded);
    });
  }
});




function handleImageError(img) {
  img.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkltYWdlIG5vdCBmb3VuZDwvdGV4dD48L3N2Zz4=';
  img.alt = 'Imagen no disponible';
}


document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('img').forEach(img => {
    img.addEventListener('error', function () {
      handleImageError(this);
    });
  });
});

function toggleForms(formType) {
  const registerForm = document.getElementById('registerFormContainer');
  const loginForm = document.getElementById('loginFormContainer');
  const modalTitle = document.getElementById('registerTitle');

  if (formType === 'login') {
    registerForm.style.display = 'none';
    loginForm.style.display = 'block';
  } else {
    registerForm.style.display = 'block';
    loginForm.style.display = 'none';
  }
}

document.addEventListener('DOMContentLoaded', function () {
  const flashMessages = document.querySelectorAll('.flash-message');
  flashMessages.forEach(message => {
    setTimeout(() => {
      message.style.transition = 'opacity 0.5s ease';
      message.style.opacity = '0';
      setTimeout(() => {
        message.remove();
      }, 500);
    }, 3000);
  });
});