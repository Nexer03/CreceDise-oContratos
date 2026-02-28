<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require __DIR__ . '/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'Usuario no autenticado']);
    exit;
}

$usuario_id = intval($_SESSION['usuario_id']);
$contenido  = trim($_POST['contenido'] ?? '');

if ($contenido === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'El contenido no puede estar vacío']);
    exit;
}

if (mb_strlen($contenido) < 3 || mb_strlen($contenido) > 2000) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Contenido inválido (3–2000 caracteres)']);
    exit;
}

try {

    // Insertar mensaje
    $stmt = $pdo->prepare("INSERT INTO foro_mensajes (usuario_id, contenido) VALUES (?, ?)");
    $stmt->execute([$usuario_id, $contenido]);

    $id = $pdo->lastInsertId();

    // Obtener nombre del usuario
    $stmtUser = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ?");
    $stmtUser->execute([$usuario_id]);
    $nombre = $stmtUser->fetchColumn();

    echo json_encode([
        'ok'             => true,
        'id'             => $id,
        'usuario_id'     => $usuario_id,
        'nombre_usuario' => htmlspecialchars($nombre, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        'contenido'      => htmlspecialchars($contenido, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        'creado_en'      => date('c')
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'msg' => 'Error al guardar mensaje',
        'error' => $e->getMessage()
    ]);
}