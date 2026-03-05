<?php
// save_courses.php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/config.php';

$envPath = __DIR__ . '/../.env';
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['usuario_id'])) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'msg' => 'No autenticado']);
  exit;
}

$userId  = (int) $_SESSION['usuario_id'];
$courseId = (int) ($_POST['course_id'] ?? 0);
$checked  = (int) ($_POST['checked'] ?? 0); // 1 = marcado

// SOLO guardar cuando se activa
if ($checked !== 1) {
  echo json_encode(['ok' => true, 'saved' => false]);
  exit;
}

if ($courseId <= 0) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'msg' => 'course_id inválido']);
  exit;
}

// Validar que el curso exista y esté activo (evita basura / IDs inventados)
$st = $pdo->prepare("SELECT 1 FROM courses WHERE id = ? AND is_active = 1");
$st->execute([$courseId]);
if (!$st->fetchColumn()) {
  http_response_code(404);
  echo json_encode(['ok' => false, 'msg' => 'Curso no existe o está inactivo']);
  exit;
}

// Insertar (tu PK compuesta evita duplicados: user_id + course_id)
$st = $pdo->prepare("
  INSERT INTO user_courses (user_id, course_id, status, progress)
  VALUES (?, ?, 'pendiente', 0)
  ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP
");
$st->execute([$userId, $courseId]);

echo json_encode(['ok' => true, 'saved' => true]);