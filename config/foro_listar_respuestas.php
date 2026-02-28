<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/database.php';
header('Content-Type: application/json; charset=utf-8');

$mensajeId = intval($_GET['mensaje_id'] ?? 0);

if ($mensajeId <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'ID inválido']);
    exit;
}

try {

    $sql = "
        SELECT fr.id, u.nombre AS nombre_usuario, fr.contenido, fr.creado_en
        FROM foro_respuestas fr
        JOIN usuarios u ON fr.usuario_id = u.id
        WHERE fr.mensaje_id = ?
        ORDER BY fr.creado_en ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$mensajeId]);

    $items = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['nombre_usuario'] = htmlspecialchars($row['nombre_usuario'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $row['contenido'] = htmlspecialchars($row['contenido'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $items[] = $row;
    }

    echo json_encode(['ok' => true, 'items' => $items]);

} catch (PDOException $e) {
    echo json_encode([
        'ok' => false,
        'msg' => 'Error al listar respuestas',
        'debug' => $e->getMessage()
    ]);
}