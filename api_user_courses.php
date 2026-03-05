<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/config.php';

if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'No autenticado']);
    exit;
}

$userId = (int) $_SESSION['usuario_id'];
$action = $_POST['action'] ?? '';
$courseId = (int)($_POST['course_id'] ?? 0);

if ($courseId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'ID de curso inválido']);
    exit;
}

if ($action === 'delete') {
    $stmt = $pdo->prepare("DELETE FROM user_courses WHERE user_id = ? AND course_id = ?");
    $result = $stmt->execute([$userId, $courseId]);
    echo json_encode(['ok' => $result]);
    exit;
} elseif ($action === 'update_status') {
    $status = $_POST['status'] ?? 'pendiente';
    $valid_statuses = ['pendiente', 'en curso', 'completado'];
    if (!in_array($status, $valid_statuses)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Estado no válido']);
        exit;
    }
    
    $stmt = $pdo->prepare("UPDATE user_courses SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND course_id = ?");
    $result = $stmt->execute([$status, $userId, $courseId]);
    echo json_encode(['ok' => $result]);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'msg' => 'Acción inválida']);
exit;
