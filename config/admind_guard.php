<?php
if (!isset($_SESSION['usuario_id'])) {
  header("Location: index.php"); exit;
}
if (empty($_SESSION['is_admin'])) {
  http_response_code(403);
  die("Acceso denegado.");
}