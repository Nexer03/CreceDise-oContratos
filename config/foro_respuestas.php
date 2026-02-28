<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/database.php';

// Verifica sesión
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false, 'error' => 'Usuario no autenticado']);
    exit;
}

$usuario_id = intval($_SESSION['usuario_id']);
$mensajeId  = intval($_POST['mensaje_id'] ?? 0);
$contenido  = trim($_POST['contenido'] ?? '');

if ($mensajeId <= 0 || mb_strlen($contenido) < 3) {
    echo json_encode(['ok' => false, 'error' => 'Datos inválidos']);
    exit;
}

try {

    // Insertar respuesta
    $stmt = $pdo->prepare("
        INSERT INTO foro_respuestas (mensaje_id, usuario_id, contenido)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$mensajeId, $usuario_id, $contenido]);

    $id = $pdo->lastInsertId();

    // Obtener nombre del usuario
    $queryUser = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ?");
    $queryUser->execute([$usuario_id]);
    $nombre_usuario = $queryUser->fetchColumn();

    echo json_encode([
        'ok'             => true,
        'id'             => $id,
        'mensaje_id'     => $mensajeId,
        'nombre_usuario' => htmlspecialchars($nombre_usuario, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        'contenido'      => htmlspecialchars($contenido, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        'creado_en'      => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'ok' => false,
        'error' => 'Error al guardar',
        'debug' => $e->getMessage()
    ]);
}