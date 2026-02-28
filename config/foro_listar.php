<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/database.php';
header('Content-Type: application/json; charset=utf-8');

$limit  = max(1, min(50, intval($_GET['limit']  ?? 20)));
$offset = max(0, intval($_GET['offset'] ?? 0));

try {

    $sql = "
        SELECT 
            f.id,
            u.nombre AS nombre_usuario,
            f.contenido,
            f.creado_en
        FROM foro_mensajes AS f
        INNER JOIN usuarios AS u ON f.usuario_id = u.id
        ORDER BY f.creado_en DESC
        LIMIT $limit OFFSET $offset
    ";

    $stmt = $pdo->query($sql);

    $items = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['nombre_usuario'] = htmlspecialchars($row['nombre_usuario'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $row['contenido']      = htmlspecialchars($row['contenido'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $items[] = $row;
    }

    echo json_encode([
        'ok'    => true,
        'items' => $items
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'msg' => 'Error al listar mensajes',
        'debug' => $e->getMessage()
    ]);
}