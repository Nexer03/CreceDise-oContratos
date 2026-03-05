<?php
session_start();
$nombre_usuario = $_SESSION['usuario_nombre'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Mi Perfil – Crece Diseño</title>

  <!-- Tipografías -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Iconos -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- AOS -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet"/>

  <style>
    /* Estilos específicos para el perfil */
    .profile-header {
      background: linear-gradient(135deg, var(--bright-blue), var(--dark-purple));
      color: white;
      padding: 3.5rem 0 2rem;
      margin-bottom: 2rem;
    }
    /* Variables CSS */
:root {
  --primary-color: #5B4393;
  --secondary-color: #F2F2F2;
  --accent-color: #FF6B6B;
  --text-color: #333333;
  --light-text: #FFFFFF;
  --dark-blue: #1A365D;
  --bright-blue: #4A6CF7;
  --dark-purple: #5B4393;
  --light-gray: #F8F9FA;
  --medium-gray: #E9ECEF;
  --dark-gray: #6C757D;
}

/* Estilos generales */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Quicksand', sans-serif;
  color: var(--text-color);
  line-height: 1.6;
  overflow-x: hidden;
  background-color: #ffffff;
  background-image: 
    radial-gradient(circle at 25% 25%, rgba(220, 220, 220, 0.2) 0%, transparent 55%),
    radial-gradient(circle at 75% 75%, rgba(220, 220, 220, 0.2) 0%, transparent 55%),
    linear-gradient(45deg, transparent 49%, rgba(220, 220, 220, 0.1) 49%, rgba(220, 220, 220, 0.1) 51%, transparent 51%),
    linear-gradient(-45deg, transparent 49%, rgba(220, 220, 220, 0.1) 49%, rgba(220, 220, 220, 0.1) 51%, transparent 51%);
  background-size: 60px 60px, 60px 60px, 20px 20px, 20px 20px;
  background-position: 0 0, 30px 30px, 0 0, 0 0;
}

h1, h2, h3, h4, h5, h6 {
  font-family: 'Montserrat', sans-serif;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

a {
  text-decoration: none;
  color: inherit;
  transition: all 0.3s ease;
}

/* Navbar */
.custom-navbar {
  padding: 0.75rem 0;
  transition: all 0.3s ease;
  background-color: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
}

.navbar-scrolled {
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.navbar-hidden {
  transform: translateY(-100%);
}

.brand-logo {
  height: 40px;
  width: auto;
}



/* Hero Section */
.hero {
  position: relative;
  overflow: hidden;
  padding: 8rem 0 4rem;
  
}

.hero-usuario {
  position: relative;
  overflow: hidden;
  padding: 10rem 0 4rem;
  margin-top: 180px; /* separa del navbar */
  background: none !important;
  color: #1A365D; /* texto oscuro para contraste */
}

.hero-usuario .banner-container {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 0;
  background: url("../recursos/patron1.svg") center/contain repeat;
  opacity: 1; 
}

.hero-usuario .banner-container img {
  display: none; 
}

.hero-usuario .hero-content {
  position: relative;
  z-index: 2;
  text-align: center;
}

.hero-usuario h1,
.hero-usuario p,
.hero-usuario .hero-stat-number,
.hero-usuario .hero-stat-label {
  color: #fff; 
}


.hero-usuario h1 {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 1rem;
}

.hero-usuario p {
  font-size: 1.2rem;
  max-width: 600px;
  margin: 0 auto 2rem;
}

.hero-stats {
  display: flex;
  justify-content: center;
  gap: 3rem;
  margin-top: 2rem;
  flex-wrap: wrap;
}

.hero-stat {
  text-align: center;
  padding: 1rem;
}

.hero-stat-number {
  display: block;
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.hero-stat-label {
  font-size: 1rem;
  opacity: 0.9;
}

.banner-container {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  opacity: 0.1;
}

.banner-container img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.hero-content {
  position: relative;
  z-index: 2;
}

.center-block {
  text-align: center;
  max-width: 800px;
  margin: 0 auto;
}

.hero h1 {
  font-size: 3rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
}

.hero p {
  font-size: 1.25rem;
  margin-bottom: 2rem;
  opacity: 0.9;
}

/* Cards */
.card {
  border: none;
  border-radius: 12px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.08);
  margin-bottom: 1.5rem;
  transition: all 0.3s ease;
}

.card:hover {
  box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.card-header {
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
  border-bottom: 1px solid rgba(0,0,0,0.05);
  padding: 1rem 1.5rem;
  border-radius: 12px 12px 0 0 !important;
}

.card-title {
  color: var(--dark-purple);
  margin: 0;
  font-weight: 600;
}

.card-body {
  padding: 1.5rem;
}

/* Profile Stats */
.profile-stats {
  display: flex;
  gap: 2rem;
  margin-top: 1rem;
  justify-content: center;
}

.stat {
  text-align: center;
}

.stat-number {
  display: block;
  font-size: 2rem;
  font-weight: 700;
  color: white;
}

.stat-label {
  font-size: 0.9rem;
  color: rgba(255,255,255,0.8);
}

/* My Courses Section */
.my-courses-section {
  margin-bottom: 3rem;
}
.my-course-status {
  position: absolute;
  top: 1rem;
  right: 1rem;
  padding: 0.3rem 0.8rem;
  border-radius: 50px;
  font-size: 0.7rem;
  font-weight: 600;
  color: white;
  z-index: 5;
  transform: translateY(-16px); 
}

.my-course-header {
  position: relative;
  padding-top: 2.2rem; 
}


.section-title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--dark-purple);
  text-align: center;
}

.courses-filter {
  display: flex;
  justify-content: center;
  gap: 1rem;
  margin-bottom: 2rem;
  flex-wrap: wrap;
}

.filter-btn {
  padding: 0.5rem 1.5rem;
  border: 2px solid var(--bright-blue);
  background: transparent;
  color: var(--bright-blue);
  border-radius: 50px;
  font-weight: 600;
  transition: all 0.3s ease;
  cursor: pointer;
}

.filter-btn.active,
.filter-btn:hover {
  background: var(--bright-blue);
  color: white;
}

.my-courses-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.my-course-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.08);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: all 0.3s ease;
  position: relative;
  justify-content: space-between;
}

.my-course-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.my-course-header {
  padding: 1.5rem 1.5rem 1rem;
  border-bottom: 1px solid var(--medium-gray);
}

.my-course-header h4 {
  color: var(--dark-purple);
  margin-bottom: 0.5rem;
}

.course-category {
  display: inline-block;
  background: var(--light-gray);
  color: var(--dark-gray);
  padding: 0.25rem 0.75rem;
  border-radius: 50px;
  font-size: 0.8rem;
  font-weight: 600;
}

.my-course-body {
  padding: 1rem 1.5rem;
  flex-grow: 1;
}
.my-course-card {
  display: flex;
  flex-direction: column;
  justify-content: space-between; /* empuja el contenido hacia arriba y los botones hacia abajo */
  height: 100%; /* iguala altura entre tarjetas */
}

.my-course-body {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  height: 100%;
}

.course-actions {
  margin-top: auto; /* fuerza a que los botones se vayan al fondo */
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.5rem;
}

.course-description {
  color: var(--dark-gray);
  margin-bottom: 1rem;
}

.course-meta {
  margin-bottom: 1rem;
}

.added-date {
  font-size: 0.9rem;
  color: var(--dark-gray);
}

.course-actions {
  margin-top: auto; /* fuerza a que los botones se vayan al fondo */
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.5rem;
}
.status-selector {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.status-selector label {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--dark-gray);
}

.status-dropdown {
  padding: 0.25rem 0.5rem;
  border: 1px solid var(--medium-gray);
  border-radius: 4px;
  background: white;
  font-size: 0.9rem;
}

.btn-remove-course {
  background: #dc3545;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 3px;
  font-size: 0.6rem;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-remove-course:hover {
  background: #c82333;
}

.my-course-status {
  position: absolute;
  top: 1rem;
  right: 1rem;
  padding: 0.25rem 0.75rem;
  border-radius: 50px;
  font-size: 0.8rem;
  font-weight: 600;
  color: white;
}

.status-selected {
  background: var(--bright-blue);
}

.status-in-progress {
  background: #ffc107;
}

.status-completed {
  background: #28a745;
}

/* Empty State */
.no-courses-message {
  text-align: center;
  padding: 3rem 1rem;
}

.empty-state {
  max-width: 500px;
  margin: 0 auto;
}

.empty-state i {
  font-size: 4rem;
  color: var(--medium-gray);
  margin-bottom: 1.5rem;
}

.empty-state h4 {
  color: var(--dark-gray);
  margin-bottom: 1rem;
}

.empty-state p {
  color: var(--dark-gray);
  margin-bottom: 2rem;
}



/* WhatsApp Float */
.whatsapp-float {
  position: fixed;
  bottom: 30px;
  left: 30px;
  width: 60px;
  height: 60px;
  background: #25D366;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 10px rgba(0,0,0,0.15);
  z-index: 1000;
  transition: all 0.3s ease;
}

.whatsapp-float:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 15px rgba(0,0,0,0.2);
}

/* Responsive */
@media (max-width: 768px) {
  .hero h1 {
    font-size: 2.25rem;
  }
  
  .hero p {
    font-size: 1.1rem;
  }
  
  .profile-stats {
    flex-direction: column;
    gap: 1rem;
  }
  
  .hero-stats {
    gap: 1.5rem;
  }
  
  .hero-stat-number {
    font-size: 2rem;
  }
  
  .my-courses-grid {
    grid-template-columns: 1fr;
  }
  
  .course-actions {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .footer-container {
    grid-template-columns: 1fr;
  }
  
  .whatsapp-float {
    bottom: 20px;
    left: 20px;
    width: 50px;
    height: 50px;
  }
}
    .profile-avatar {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      border: 5px solid white;
      object-fit: cover;
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .profile-name {
      font-size: 2rem;
      font-weight: 700;
      margin-top: 1rem;
    }
    
    .profile-title {
      font-size: 1.2rem;
      opacity: 0.9;
    }
    
    .btn-edit {
      background-color: white;
      color: var(--primary-color);
      border: none;
      border-radius: 50px;
      padding: 0.5rem 1.25rem;
      font-weight: 600;
      transition: all 0.3s ease;
    }
    
    .btn-edit:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    
    .card {
      border: none;
      border-radius: 12px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.08);
      margin-bottom: 1.5rem;
      transition: all 0.3s ease;
    }
    
    .card:hover {
      box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    }
    
    .card-header {
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
      border-bottom: 1px solid rgba(0,0,0,0.05);
      padding: 1rem 1.5rem;
      border-radius: 12px 12px 0 0 !important;
    }
    
    .card-title {
      color: var(--dark-purple);
      margin: 0;
      font-weight: 600;
    }
    
    .card-body {
      padding: 1.5rem;
    }
    
    .skill-bar {
      height: 8px;
      background-color: #e9ecef;
      border-radius: 4px;
      overflow: hidden;
    }
    
    .skill-level {
      height: 100%;
      background: linear-gradient(to right, var(--bright-blue), var(--dark-purple));
      border-radius: 4px;
      transition: width 0.5s ease;
    }
    
    .editable-focused {
      outline: 2px dashed rgba(74,108,247,0.35);
      padding: 0.25rem;
      border-radius: 6px;
      background-color: rgba(74,108,247,0.05);
    }
    
    .edit-controls {
      display: flex;
      gap: 0.5rem;
      justify-content: center;
      margin-top: 1rem;
    }
    
    .small-muted {
      color: #6c757d;
      font-size: 0.9rem;
    }
    
    .btn-cancel {
      background: #f8f9fa;
      border: 1px solid #ddd;
      color: #333;
    }
    
    .skill-inputs {
      display: flex;
      gap: 0.5rem;
      align-items: center;
      margin-top: 0.5rem;
    }
    
    .skill-inputs input[type="range"] {
      width: 120px;
    }
    
    .skill-inputs input[type="number"] {
      width: 60px;
    }
    
    .timeline {
      position: relative;
      padding-left: 2rem;
    }
    
    .timeline::before {
      content: '';
      position: absolute;
      left: 7px;
      top: 0;
      bottom: 0;
      width: 2px;
      background: var(--bright-blue);
    }
    
    .timeline-item {
      position: relative;
      margin-bottom: 1.5rem;
    }
    
    .timeline-item::before {
      content: '';
      position: absolute;
      left: -2rem;
      top: 5px;
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background: var(--bright-blue);
    }
    
    .timeline-date {
      font-weight: 600;
      color: var(--dark-purple);
      margin-bottom: 0.25rem;
    }
    
    .timeline-title {
      font-weight: 600;
      margin-bottom: 0.25rem;
    }
    
    .timeline-company {
      color: var(--bright-blue);
      font-weight: 500;
      margin-bottom: 0.5rem;
    }
    
    .profile-stats {
      display: flex;
      gap: 2rem;
      margin-top: 1rem;
      justify-content: center;
    }
    
    .stat {
      text-align: center;
    }
    
    .stat-number {
      display: block;
      font-size: 2rem;
      font-weight: 700;
      color: white;
    }
    
    .stat-label {
      font-size: 0.9rem;
      color: rgba(255,255,255,0.8);
    }
    
    .profile-content {
      padding: 2rem 0;
    }
    
    .editable-field {
      padding: 0.25rem 0.5rem;
      border-radius: 4px;
      transition: all 0.2s ease;
    }
    
    .editable-field:focus {
      background-color: rgba(33, 124, 227, 0.05);
    }
    
    .cert-title {
      color: var(--dark-blue);
      margin-bottom: 0.25rem;
    }
    
    .cert-issuer {
      color: var(--bright-blue);
      margin-bottom: 0.25rem;
    }
    
    .cert-date {
      font-style: italic;
    }
    
    /* Botones de acción */
    .action-buttons {
      position: fixed;
      bottom: 30px;
      right: 100px;
      z-index: 1000;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    
    .action-btn {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 10px rgba(0,0,0,0.15);
      transition: all 0.3s ease;
      cursor: pointer;
    }
    
    .action-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 15px rgba(0,0,0,0.2);
    }
    
    .btn-edit-mode {
      background: linear-gradient(135deg, var(--bright-blue), var(--dark-purple));
      color: white;
    }
    
    .btn-save {
      background: #28a745;
      color: white;
    }
    
    .btn-cancel-edit {
      background: #dc3545;
      color: white;
    }
    
    /* Notificaciones */
    .course-notification {
      position: fixed;
      top: 100px;
      right: 20px;
      background: #5B4393;
      color: white;
      padding: 15px 20px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      z-index: 10000;
      max-width: 300px;
      animation: slideIn 0.3s ease-out;
    }
    
    @keyframes slideIn {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
      from { transform: translateX(0); opacity: 1; }
      to { transform: translateX(100%); opacity: 0; }
    }
    
    /* Fondo con patron geométrico */
    body {
      background-color: #ffffff;
      background-image: 
        radial-gradient(circle at 25% 25%, rgba(220, 220, 220, 0.2) 0%, transparent 55%),
        radial-gradient(circle at 75% 75%, rgba(220, 220, 220, 0.2) 0%, transparent 55%),
        linear-gradient(45deg, transparent 49%, rgba(220, 220, 220, 0.1) 49%, rgba(220, 220, 220, 0.1) 51%, transparent 51%),
        linear-gradient(-45deg, transparent 49%, rgba(220, 220, 220, 0.1) 49%, rgba(220, 220, 220, 0.1) 51%, transparent 51%);
      background-size: 60px 60px, 60px 60px, 20px 20px, 20px 20px;
      background-position: 0 0, 30px 30px, 0 0, 0 0;
    }
    
   
    
    /* Responsive */
    @media (max-width: 768px) {
      .profile-stats {
        flex-direction: column;
        gap: 1rem;
      }
      
      .action-buttons {
        bottom: 20px;
        right: 20px;
      }
      
      .action-btn {
        width: 45px;
        height: 45px;
      }
      
      .hero-stats {
        gap: 1.5rem;
      }
      
      .hero-stat-number {
        font-size: 2rem;
      }
    }
    /* ===========================
   NAVBAR (encabezado)
   =========================== */
.custom-navbar{
  height:200px;
  background-color:rgba(255,255,255,.95);
  transition:all .4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  display:flex;align-items:center;
  transform: translateY(0);
  box-shadow: 0 2px 20px rgba(0,0,0,0.08);
}
.custom-navbar.navbar-hidden { transform: translateY(-100%); }
.custom-navbar.navbar-scrolled {
  height: 80px;
  background-color:rgba(255,255,255,.98);
  box-shadow:0 5px 20px rgba(0,0,0,.1);
}
.custom-navbar.navbar-scrolled .brand-logo { height: 60px; }

.brand-logo{
  height:140px;
  width:auto;
  display:block;
  transition: all .4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
.brand-left{ padding-left:0; margin-left:0; transform:translateX(-6px); }

/* Enlaces del menú */
.navbar-nav .nav-link{
  color:var(--dark-blue);
  font-family:'Quicksand',sans-serif;
  font-weight:600;
  font-size:1.15rem;
  position:relative;
  transition: all .3s ease;
  padding:0.8rem 1.2rem !important;
  margin:0 0.3rem;
  border-radius:8px;
  display:inline-block;
}
.navbar-nav .nav-link:hover{
  color:var(--bright-blue);
  background-color:rgba(33,124,227,0.1);
  transform:translateY(-2px);
}
.navbar-nav .nav-link.active{
  color:var(--bright-blue);
  background-color:rgba(33,124,227,0.15);
}

/* Subrayado centrado (hover/active) */
.navbar-nav .nav-link{ position:relative; }
.navbar-nav .nav-link::after{
  content:"";
  position:absolute;
  left:50%;
  bottom:-6px;
  height:2px;
  width:0;
  background:var(--bright-blue);
  border-radius:1px;
  transform:translateX(-50%);
  transition:width .25s ease;
}
.navbar-nav .nav-link:hover::after{ width:100%; }
.navbar-nav .nav-link.active::after{ width:70%; }
.custom-navbar.navbar-scrolled .navbar-nav .nav-link{
  font-size:1.1rem;
  padding:0.6rem 1rem !important;
}
.custom-navbar.navbar-scrolled .navbar-nav .nav-link::after{ bottom:-4px; }

/* Responsive: menú desplegable con #mainNav */
@media (max-width: 991px){
  #mainNav{
    display:none;
    position:absolute;
    top:100%;
    left:0;
    width:100%;
    background:#fff;
    padding:10px 0;
    box-shadow:0 4px 8px rgba(0,0,0,0.1);
    z-index:1001;
  }
  #mainNav.show{ display:block; }
  #mainNav .navbar-nav{
    flex-direction:column;
    align-items:stretch !important;
  }
  #mainNav .nav-link{
    font-size:1rem;
    padding:0.6rem 1rem !important;
    margin:0.15rem 0;
    text-align:center;
    border-radius:8px;
    width:100%;
  }
  #mainNav .nav-link::after{ display:none !important; }
}

/* Alturas/logo en otros breakpoints */
@media (max-width: 1200px){ .brand-logo{height:120px} }
@media (max-width: 992px){
  .custom-navbar{height:150px}
  .custom-navbar.navbar-scrolled{ height:70px; }
  .custom-navbar.navbar-scrolled .brand-logo{ height:50px; }
}
@media (max-width: 768px){
  .brand-logo{height:100px}
  .custom-navbar.navbar-scrolled{ height:60px; }
  .custom-navbar.navbar-scrolled .brand-logo{ height:45px; }
}
@media (max-width: 576px){
  .brand-logo{height:80px}
  .custom-navbar.navbar-scrolled{ height:55px; }
  .custom-navbar.navbar-scrolled .brand-logo{ height:40px; }
}

/* ===========================
   FOOTER (pie de página)
   =========================== */

footer{
  background-color:rgba(26, 28, 54, 0.959);
  color:var(--white);
  padding:60px 0 30px;
  position: relative;
  overflow: hidden;
}
footer::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" fill="none"><defs><pattern id="footerPattern" x="0" y="0" width="25" height="25" patternUnits="userSpaceOnUse"><circle cx="12.5" cy="12.5" r="1" fill="%23217CE3" opacity="0.2"/></pattern></defs><rect width="100" height="100" fill="url(%23footerPattern)"/></svg>');
  z-index: 0;
}
footer > .container{ position:relative; z-index:1; }

.footer-container{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
  gap:40px;
  margin-bottom:40px;
}
.footer-col h3{
  color: #fff !important;
  margin-bottom:20px;
  font-size:1.2rem;
  position:relative;
  display:inline-block;
}
.footer-col h3::after{
  content:"";
  position:absolute;
  bottom:-5px; left:0;
  width:30px; height:2px;
  background:var(--bright-blue);
}
.footer-col p,
.footer-col a{
  color:rgba(255,255,255,.7);
  margin-bottom:10px;
  display:block;
  text-decoration:none;
  transition:color .3s ease;
}
.footer-col a:hover{
  color:var(--bright-blue);
  transform:translateX(5px);
}
.social-links {
  display: flex;
  gap: 14px;
  margin-top: 18px;
  justify-content: center;
}

.social-link {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  background-color: rgba(255, 255, 255, 0.12);
  border-radius: 50%;
  color: rgba(255, 255, 255, 0.9);
  text-decoration: none;
  font-size: 1.3rem;
  line-height: 1;
  transition: all 0.25s ease;
  backdrop-filter: blur(2px);
  box-shadow: 0 0 4px rgba(255, 255, 255, 0.05);
}

.social-link:hover {
  background-color: rgba(33, 124, 227, 0.25); /* fondo un poco más claro, no lo cubre */
  color: #ffffff;
  text-shadow: 0 0 8px rgba(255, 255, 255, 0.85); /* brillo suave */
  box-shadow: 0 0 10px rgba(33, 124, 227, 0.4);
  transform: translateY(-3px) scale(1.08);
}

.social-link i {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  pointer-events: none; /* evita saltos */
}
.copyright{
  text-align:center;
  padding-top:30px;
  border-top:1px solid rgba(255,255,255,.1);
  color:rgba(255,255,255,.5);
  font-size:.9rem
}


/* Footer en móvil: centrado del subrayado de h3 */
@media (max-width: 576px){
  .footer-container{ grid-template-columns:1fr; text-align:center; }
  .footer-col h3::after{ left:50%; transform:translateX(-50%); }
}

  </style>
</head>
<body>
  
   <header class="shadow-sm">
    <nav class="navbar navbar-expand-lg bg-white-95 fixed-top custom-navbar" id="mainNavbar">
      <div class="container-fluid px-2 px-sm-3 px-lg-4">
        
        <a class="navbar-brand d-flex align-items-center me-auto brand-left" href="index.html">
          <img src="../recursos/logo.svg" alt="Crece Diseño" class="brand-logo" />
        </a>
        <button class="navbar-toggler ms-2 mobile-menu-btn" type="button" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="navbar-collapse" id="mainNav">
          <ul class="navbar-nav ms-auto align-items-lg-center">
            <li class="nav-item"><a class="nav-link" href="../index.html">Inicio</a></li>
            <li class="nav-item"><a class="nav-link" href="../html/cursos.html">Cursos</a></li>
            <li class="nav-item"><a class="nav-link" href="../html/nosotros.html">Nosotros</a></li>
            <li class="nav-item"><a class="nav-link" href="../html/foro.php">Foro</a></li>
            <li class="nav-item"><a class="nav-link" href="../html/usuario.html">Mi Perfil</a></li>
          </ul>
        </div>
      </div>
    </nav>
  </header>

  <!-- Hero con estadísticas -->
  <section class="hero hero-usuario" id="inicio">
    <div class="banner-container">
      <img src="../recursos/patron1.svg" alt="Patrón de fondo" />
    </div>
    <div class="container">
      <div class="hero-content center-block" data-aos="fade-up" data-aos-duration="800">
        <h1>Mi Perfil de Aprendizaje</h1>
        <p>Gestiona tus cursos seleccionados y sigue tu progreso de formación profesional.</p>
        
        <!-- Estadísticas de cursos -->
        <div class="hero-stats" data-aos="fade-up" data-aos-delay="300">
          <div class="hero-stat">
            <span class="hero-stat-number" id="total-courses-hero">0</span>
            <span class="hero-stat-label">Cursos Totales</span>
          </div>
          <div class="hero-stat">
            <span class="hero-stat-number" id="in-progress-hero">0</span>
            <span class="hero-stat-label">En Progreso</span>
          </div>
          <div class="hero-stat">
            <span class="hero-stat-number" id="completed-hero">0</span>
            <span class="hero-stat-label">Completados</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Botones de acción flotantes -->
  <div class="action-buttons">
    <div class="action-btn btn-edit-mode" id="btnEdit" title="Editar perfil">
      <i class="fas fa-edit"></i>
    </div>
    <div class="action-btn btn-save" id="btnSave" title="Guardar cambios" style="display: none;">
      <i class="fas fa-save"></i>
    </div>
    <div class="action-btn btn-cancel-edit" id="btnCancel" title="Cancelar edición" style="display: none;">
      <i class="fas fa-times"></i>
    </div>
  </div>

  <main class="profile-content">
    <div class="container">
      <div class="row">
        <!-- Left column -->
        <div class="col-lg-8">
          <!-- About -->
          <div class="card" id="aboutCard" data-section="about">
            <div class="card-header">
              <h3 class="card-title">Acerca de mí</h3>
            </div>
            <div class="card-body" id="aboutBody">
              <p class="about-text" data-editable="true">Diseñadora gráfica con 5 años de experiencia especializada en branding, ilustración digital y diseño UX/UI. Me apasiona crear identidades visuales que cuentan historias y resuelven problemas de comunicación.</p>
              <p class="about-text" data-editable="true">Actualmente trabajo como diseñadora senior en una agencia digital, donde lidero proyectos de diseño para clientes internacionales. Mi enfoque combina creatividad, estrategia y atención al detalle para entregar soluciones visuales efectivas.</p>
            </div>
          </div>

          <!-- Experience -->
          <div class="card" id="experienceCard" data-section="experience">
            <div class="card-header">
              <h3 class="card-title">Experiencia Profesional</h3>
            </div>
            <div class="card-body" id="experienceBody">
              <div class="timeline">
                <div class="timeline-item" data-editable="true">
                  <div class="timeline-date">2021 - Presente</div>
                  <h4 class="timeline-title">Diseñadora Senior</h4>
                  <div class="timeline-company">Estudio Creativo Digital</div>
                  <p>Lidero proyectos de diseño de marca y experiencia de usuario para clientes internacionales. Superviso un equipo de 3 diseñadores junior.</p>
                </div>
                
                <div class="timeline-item" data-editable="true">
                  <div class="timeline-date">2019 - 2021</div>
                  <h4 class="timeline-title">Diseñadora Gráfica</h4>
                  <div class="timeline-company">Agencia Branding Solutions</div>
                  <p>Diseñé identidades visuales, material promocional y sitios web para pequeñas y medianas empresas.</p>
                </div>
              </div>
              <div class="small-muted mt-2">Tip: Haz clic en el texto para editar. Puedes añadir o eliminar contenido si lo deseas.</div>
            </div>
          </div>
        </div>

        <!-- Right column -->
        <div class="col-lg-4">
          <!-- Contact -->
          <div class="card mb-4" id="contactCard" data-section="contact">
            <div class="card-header">
              <h3 class="card-title">Información de Contacto</h3>
            </div>
            <div class="card-body" id="contactBody">
              <div class="mb-3" data-editable="true">
                <strong><i class="fas fa-envelope me-2 text-primary"></i> Email</strong>
                <p class="mb-0 editable-field">ana.garcia@ejemplo.com</p>
              </div>
              <div class="mb-3" data-editable="true">
                <strong><i class="fas fa-phone me-2 text-primary"></i> Teléfono</strong>
                <p class="mb-0 editable-field">+52 322 555 7890</p>
              </div>
              <div class="mb-3" data-editable="true">
                <strong><i class="fas fa-map-marker-alt me-2 text-primary"></i> Ubicación</strong>
                <p class="mb-0 editable-field">Puerto Vallarta, Jalisco, México</p>
              </div>
              <div class="mb-3" data-editable="true">
                <strong><i class="fas fa-globe me-2 text-primary"></i> Sitio Web</strong>
                <p class="mb-0 editable-field">www.anagarcia-design.com</p>
              </div>
            </div>
          </div>

          <!-- Skills -->
          <div class="card mb-4" id="skillsCard" data-section="skills">
            <div class="card-header">
              <h3 class="card-title">Habilidades</h3>
            </div>
            <div class="card-body" id="skillsBody">
              <!-- skill template entries -->
              <div class="skill-item" data-index="0">
                <div class="skill-name d-flex justify-content-between align-items-center">
                  <span class="skill-text" data-editable="true">Diseño de Marca</span>
                  <span class="skill-percent">95%</span>
                </div>
                <div class="skill-bar mt-1"><div class="skill-level" style="width:95%"></div></div>
              </div>

              <div class="skill-item" data-index="1">
                <div class="skill-name d-flex justify-content-between align-items-center">
                  <span class="skill-text" data-editable="true">Ilustración Digital</span>
                  <span class="skill-percent">90%</span>
                </div>
                <div class="skill-bar mt-1"><div class="skill-level" style="width:90%"></div></div>
              </div>

              <div class="skill-item" data-index="2">
                <div class="skill-name d-flex justify-content-between align-items-center">
                  <span class="skill-text" data-editable="true">Diseño UX/UI</span>
                  <span class="skill-percent">85%</span>
                </div>
                <div class="skill-bar mt-1"><div class="skill-level" style="width:85%"></div></div>
              </div>

              <div class="skill-item" data-index="3">
                <div class="skill-name d-flex justify-content-between align-items-center">
                  <span class="skill-text" data-editable="true">Adobe Creative Suite</span>
                  <span class="skill-percent">95%</span>
                </div>
                <div class="skill-bar mt-1"><div class="skill-level" style="width:95%"></div></div>
              </div>

              <div class="skill-item" data-index="4">
                <div class="skill-name d-flex justify-content-between align-items-center">
                  <span class="skill-text" data-editable="true">Figma</span>
                  <span class="skill-percent">80%</span>
                </div>
                <div class="skill-bar mt-1"><div class="skill-level" style="width:80%"></div></div>
              </div>

              <div class="small-muted mt-2">En modo edición puedes ajustar el porcentaje usando el control deslizante y el número.</div>
            </div>
          </div>

          <!-- Certifications -->
          <div class="card" id="certsCard" data-section="certifications">
            <div class="card-header">
              <h3 class="card-title">Certificaciones</h3>
            </div>
            <div class="card-body" id="certsBody">
              <div class="mb-3" data-editable="true">
                <h5 class="mb-1 cert-title">Especialista en Diseño de Marca</h5>
                <p class="mb-1 text-muted cert-issuer">Crece Diseño</p>
                <small class="text-muted cert-date">Obtenida: Marzo 2023</small>
              </div>
              <div class="mb-3" data-editable="true">
                <h5 class="mb-1 cert-title">Diseñador UX/UI Avanzado</h5>
                <p class="mb-1 text-muted cert-issuer">Crece Diseño</p>
                <small class="text-muted cert-date">Obtenida: Enero 2023</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
  
  <section class="main-content">
    <div class="container">
      <!-- Sección de Cursos Seleccionados -->
      <div class="my-courses-section">
        <h2 class="section-title mb-4">Mis Cursos</h2>
        
        <div class="courses-filter">
          <button class="filter-btn active" data-filter="all">Todos</button>
          <button class="filter-btn" data-filter="seleccionado">Seleccionados</button>
          <button class="filter-btn" data-filter="en-progreso">En Progreso</button>
          <button class="filter-btn" data-filter="completado">Completados</button>
        </div>

        <div id="no-courses-message" class="no-courses-message" style="display: none;">
          <div class="empty-state">
            <i class="fas fa-book-open"></i>
            <h4>No tienes cursos seleccionados</h4>
            <p>Visita nuestro catálogo de cursos y selecciona los que te interesen para comenzar tu formación.</p>
            <a href="./cursos.html" class="btn btn-primary">Explorar Cursos</a>
          </div>
        </div>

        <div id="my-courses-grid" class="my-courses-grid">
          <!-- Los cursos seleccionados se cargarán aquí dinámicamente -->
        </div>
      </div>
    </div>
  </section>

  <!-- Footer igual a index -->
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
          <a href="../index.html">Inicio</a>
          <a href="../html/cursos.html">Cursos</a>
          <a href="../html/nosotros.html">Nosotros</a>
          <a href="../html/foro.php">Foro</a>
          <a href="../html/usuario.html">Mi Perfil</a>
        </div>
        <div class="footer-col">
          <h3>Cursos</h3>
          <a href="html/cursos.html#gratuitos">Cursos Gratuitos</a>
          <a href="html/cursos.html#paga">Cursos de Paga</a>
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


  <!-- Botón WhatsApp igual a index -->
  <a class="whatsapp-float" target="_blank" rel="noopener"
     href="https://whatsapp.com/channel/0029VbBAW0D3LdQPDltrrs1N">
    <i class="fab fa-whatsapp"></i>
  </a>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
  <script>
    AOS.init();

    // Navbar sticky
    (function(){
      const navbar = document.getElementById('mainNavbar');
      let lastY = window.scrollY;
      window.addEventListener('scroll', () => {
        const y = window.scrollY;
        navbar.classList.toggle('navbar-scrolled', y > 120);
        navbar.classList.toggle('navbar-hidden', y > lastY && y > 180);
        lastY = y;
      }, { passive: true });
    })();

    // --- SISTEMA DE GESTIÓN DE CURSOS DEL USUARIO ---
    document.addEventListener('DOMContentLoaded', function() {
      loadUserCourses();
      setupFilterButtons();
      loadSavedData();
    });

    function loadUserCourses() {
      const selectedCourses = JSON.parse(localStorage.getItem('selectedCourses') || '{}');
      const coursesGrid = document.getElementById('my-courses-grid');
      const noCoursesMessage = document.getElementById('no-courses-message');
      
      // Actualizar estadísticas
      updateStats(selectedCourses);
      
      // Limpiar grid
      coursesGrid.innerHTML = '';
      
      if (Object.keys(selectedCourses).length === 0) {
        noCoursesMessage.style.display = 'block';
        coursesGrid.style.display = 'none';
        return;
      }
      
      noCoursesMessage.style.display = 'none';
      coursesGrid.style.display = 'grid';
      
      // Crear tarjetas para cada curso
      Object.entries(selectedCourses).forEach(([courseId, course]) => {
        const courseCard = createCourseCard(courseId, course);
        coursesGrid.appendChild(courseCard);
      });
    }

    function createCourseCard(courseId, course) {
      const card = document.createElement('div');
      card.className = 'my-course-card';
      card.setAttribute('data-status', course.status);
      
      // Determinar clase de estado
      let statusClass = '';
      let statusText = '';
      
      switch(course.status) {
        case 'seleccionado':
          statusClass = 'status-selected';
          statusText = 'Seleccionado';
          break;
        case 'en-progreso':
          statusClass = 'status-in-progress';
          statusText = 'En Progreso';
          break;
        case 'completado':
          statusClass = 'status-completed';
          statusText = 'Completado';
          break;
      }
      
      card.innerHTML = `
        <div class="my-course-header">
          <h4>${course.name}</h4>
          <span class="course-category">${course.category}</span>
        </div>
        <div class="my-course-body">
          <p class="course-description">${course.description}</p>
          <div class="course-meta">
            <span class="added-date">Agregado: ${formatDate(course.addedDate)}</span>
          </div>
          <div class="course-actions">
            <div class="status-selector">
              <label>Estado:</label>
              <select class="status-dropdown" data-course-id="${courseId}">
                <option value="seleccionado" ${course.status === 'seleccionado' ? 'selected' : ''}>Seleccionado</option>
                <option value="en-progreso" ${course.status === 'en-progreso' ? 'selected' : ''}>En Progreso</option>
                <option value="completado" ${course.status === 'completado' ? 'selected' : ''}>Completado</option>
              </select>
            </div>
            <button class="btn-remove-course" data-course-id="${courseId}">
              <i class="fas fa-trash"></i> Eliminar
            </button>
          </div>
        </div>
        <div class="my-course-status ${statusClass}">
          <span>${statusText}</span>
        </div>
      `;
      
      return card;
    }

    function formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString('es-MX', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });
    }

    function updateStats(courses) {
      const total = Object.keys(courses).length;
      const inProgress = Object.values(courses).filter(course => course.status === 'en-progreso').length;
      const completed = Object.values(courses).filter(course => course.status === 'completado').length;
      
      // Actualizar estadísticas en el hero
      document.getElementById('total-courses-hero').textContent = total;
      document.getElementById('in-progress-hero').textContent = inProgress;
      document.getElementById('completed-hero').textContent = completed;
    }

    function setupFilterButtons() {
      const filterButtons = document.querySelectorAll('.courses-filter .filter-btn');
      
      filterButtons.forEach(btn => {
        btn.addEventListener('click', function() {
          // Actualizar botones activos
          filterButtons.forEach(b => b.classList.remove('active'));
          this.classList.add('active');
          
          // Filtrar cursos
          const filter = this.getAttribute('data-filter');
          filterCourses(filter);
        });
      });
      
      // Configurar eventos para los dropdowns de estado
      document.addEventListener('change', function(e) {
        if (e.target.classList.contains('status-dropdown')) {
          const courseId = e.target.getAttribute('data-course-id');
          const newStatus = e.target.value;
          updateCourseStatus(courseId, newStatus);
        }
      });
      
      // Configurar eventos para los botones de eliminar
      document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-remove-course') || 
            e.target.closest('.btn-remove-course')) {
          const button = e.target.classList.contains('btn-remove-course') ? 
                         e.target : e.target.closest('.btn-remove-course');
          const courseId = button.getAttribute('data-course-id');
          removeCourse(courseId);
        }
      });
    }

    function filterCourses(filter) {
      const courseCards = document.querySelectorAll('.my-course-card');
      
      courseCards.forEach(card => {
        if (filter === 'all' || card.getAttribute('data-status') === filter) {
          card.style.display = 'flex';
        } else {
          card.style.display = 'none';
        }
      });
    }

    function updateCourseStatus(courseId, newStatus) {
      const selectedCourses = JSON.parse(localStorage.getItem('selectedCourses') || '{}');
      
      if (selectedCourses[courseId]) {
        selectedCourses[courseId].status = newStatus;
        localStorage.setItem('selectedCourses', JSON.stringify(selectedCourses));
        
        // Recargar la vista
        loadUserCourses();
        
        // Mostrar notificación
        showNotification(`Estado del curso actualizado a "${getStatusText(newStatus)}"`);
      }
    }

    function getStatusText(status) {
      switch(status) {
        case 'seleccionado': return 'Seleccionado';
        case 'en-progreso': return 'En Progreso';
        case 'completado': return 'Completado';
        default: return status;
      }
    }

    function removeCourse(courseId) {
      if (confirm('¿Estás seguro de que quieres eliminar este curso de tu lista?')) {
        const selectedCourses = JSON.parse(localStorage.getItem('selectedCourses') || '{}');
        
        if (selectedCourses[courseId]) {
          delete selectedCourses[courseId];
          localStorage.setItem('selectedCourses', JSON.stringify(selectedCourses));
          
          // Recargar la vista
          loadUserCourses();
          
          // Mostrar notificación
          showNotification('Curso eliminado de tu lista');
        }
      }
    }

    function showNotification(message) {
      // Crear elemento de notificación
      const notification = document.createElement('div');
      notification.className = 'course-notification';
      notification.textContent = message;
      
      // Agregar al DOM
      document.body.appendChild(notification);
      
      // Remover después de 3 segundos
      setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-in';
        setTimeout(() => {
          if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
          }
        }, 300);
      }, 3000);
    }

    // --- SISTEMA DE EDICIÓN DE PERFIL ---
    const btnEdit = document.getElementById('btnEdit');
    const btnSave = document.getElementById('btnSave');
    const btnCancel = document.getElementById('btnCancel');

    // Toggle edit mode
    let isEditing = false;
    btnEdit.addEventListener('click', enterEditMode);
    btnSave.addEventListener('click', () => exitEditMode(true));
    btnCancel.addEventListener('click', () => exitEditMode(false));

    function enterEditMode() {
      isEditing = true;
      btnEdit.style.display = 'none';
      btnSave.style.display = 'flex';
      btnCancel.style.display = 'flex';
      enableContentEditing(true);
    }

    function exitEditMode(save) {
      if (save) saveChanges();
      isEditing = false;
      btnEdit.style.display = 'flex';
      btnSave.style.display = 'none';
      btnCancel.style.display = 'none';
      enableContentEditing(false);
    }

    // Enable or disable contentEditable and show skill inputs during edit
    function enableContentEditing(flag) {
      // Generic editable elements
      document.querySelectorAll('[data-editable]').forEach(el => {
        el.contentEditable = flag ? "true" : "false";
        if (flag) {
          el.classList.add('editable-focused');
        } else {
          el.classList.remove('editable-focused');
        }
      });

      // Skill items need numeric inputs for percentage
      const skillItems = document.querySelectorAll('#skillsBody .skill-item');
      skillItems.forEach((item, idx) => {
        const percentSpan = item.querySelector('.skill-percent');
        const levelBar = item.querySelector('.skill-level');
        const nameSpan = item.querySelector('.skill-text');

        if (flag) {
          // Hide percent span and inject inputs
          percentSpan.style.display = 'none';
          // create container for inputs if not exists
          if (!item.querySelector('.skill-inputs')) {
            const inputs = document.createElement('div');
            inputs.className = 'skill-inputs mt-2';
            // range
            const range = document.createElement('input');
            range.type = 'range';
            range.min = 0; range.max = 100;
            const current = parseInt(levelBar.style.width) || 0;
            range.value = current;
            range.addEventListener('input', () => {
              num.value = range.value;
              levelBar.style.width = range.value + '%';
            });
            // number
            const num = document.createElement('input');
            num.type = 'number';
            num.min = 0; num.max = 100;
            num.value = current;
            num.addEventListener('input', () => {
              let v = parseInt(num.value) || 0;
              if (v < 0) v = 0; if (v > 100) v = 100;
              num.value = v;
              range.value = v;
              levelBar.style.width = v + '%';
            });
            inputs.appendChild(range);
            inputs.appendChild(num);

            item.appendChild(inputs);
          }
          nameSpan.contentEditable = "true";
          nameSpan.classList.add('editable-focused');
        } else {
          // remove inputs
          const inputs = item.querySelector('.skill-inputs');
          if (inputs) inputs.remove();
          percentSpan.style.display = 'inline';
          const width = parseInt(levelBar.style.width) || 0;
          percentSpan.textContent = width + '%';
          nameSpan.contentEditable = "false";
          nameSpan.classList.remove('editable-focused');
        }
      });
    }

    // Save changes to localStorage
    function saveChanges() {
      // Save About (store innerHTML)
      const aboutHTML = document.getElementById('aboutBody').innerHTML;
      localStorage.setItem('profile_about', aboutHTML);

      // Save Contact
      localStorage.setItem('profile_contact', document.getElementById('contactBody').innerHTML);

      // Save Experience
      localStorage.setItem('profile_experience', document.getElementById('experienceBody').innerHTML);

      // Save Certifications
      localStorage.setItem('profile_certs', document.getElementById('certsBody').innerHTML);

      // Save Skills - build array of {name,percent}
      const skills = [];
      document.querySelectorAll('#skillsBody .skill-item').forEach(item => {
        const name = item.querySelector('.skill-text').textContent.trim();
        const levelBar = item.querySelector('.skill-level');
        let percent = 0;
        // when in editing, range may exist
        const inputs = item.querySelector('.skill-inputs input[type="number"]');
        if (inputs) {
          percent = parseInt(inputs.value) || 0;
        } else {
          // read from bar or percent span
          percent = parseInt(levelBar.style.width) || parseInt((item.querySelector('.skill-percent')||{textContent:'0%'}).textContent) || 0;
        }
        if (percent < 0) percent = 0; if (percent > 100) percent = 100;
        skills.push({name, percent});
      });
      localStorage.setItem('profile_skills', JSON.stringify(skills));

      // Feedback
      showNotification('Cambios guardados correctamente');
    }

    // Load saved data from localStorage
    function loadSavedData() {
      const about = localStorage.getItem('profile_about');
      if (about) document.getElementById('aboutBody').innerHTML = about;

      const contact = localStorage.getItem('profile_contact');
      if (contact) document.getElementById('contactBody').innerHTML = contact;

      const exp = localStorage.getItem('profile_experience');
      if (exp) document.getElementById('experienceBody').innerHTML = exp;

      const certs = localStorage.getItem('profile_certs');
      if (certs) document.getElementById('certsBody').innerHTML = certs;

      const skills = localStorage.getItem('profile_skills');
      if (skills) {
        try {
          const arr = JSON.parse(skills);
          const container = document.getElementById('skillsBody');
          container.innerHTML = ''; // rebuild
          arr.forEach((s, i) => {
            const item = document.createElement('div');
            item.className = 'skill-item';
            item.setAttribute('data-index', i);
            item.innerHTML = `
              <div class="skill-name d-flex justify-content-between align-items-center">
                <span class="skill-text" data-editable="true">${escapeHtml(s.name)}</span>
                <span class="skill-percent">${s.percent}%</span>
              </div>
              <div class="skill-bar mt-1"><div class="skill-level" style="width:${s.percent}%"></div></div>
            `;
            container.appendChild(item);
          });
        } catch(e){ console.warn('Error parsing skills', e); }
      }
    }

    function escapeHtml(text) {
      const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      };
      return text.replace(/[&<>"']/g, function(m){ return map[m]; });
    }

    // Double-click to quickly enter edit on a block
    document.querySelectorAll('[data-editable]').forEach(el => {
      el.addEventListener('dblclick', () => {
        if (!isEditing) {
          enterEditMode();
          el.focus();
        }
      });
    });

    // Keyboard shortcut: Ctrl+E to toggle edit
    document.addEventListener('keydown', (e) => {
      if (e.ctrlKey && e.key.toLowerCase() === 'e') {
        e.preventDefault();
        if (!isEditing) enterEditMode(); else exitEditMode(true);
      }
    });

    // Make sure saving happens before unload to avoid losing edits accidentally
    window.addEventListener('beforeunload', (e) => {
      if (isEditing) {
        // Save automatically
        saveChanges();
      }
    });
  </script>
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
</body>
</html>
