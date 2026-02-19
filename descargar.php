<?php
require_once __DIR__ . '/config/database.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
  http_response_code(401);
  die("No autorizado");
}

$usuarioId = (int)$_SESSION['usuario_id'];
$product = $_GET['product'] ?? null;

if (!$product || !preg_match('/^[a-z0-9_]+$/', $product)) {
  http_response_code(400);
  die("Producto inválido");
}

/* 1) Validar compra */
$stmt = $pdo->prepare("
  SELECT id
  FROM payments
  WHERE usuario_id = :usuario_id
    AND product = :product
    AND status = 'COMPLETED'
  LIMIT 1
");
$stmt->execute([
  ':usuario_id' => $usuarioId,
  ':product' => $product
]);

if (!$stmt->fetch()) {
  http_response_code(403);
  die("No has comprado este contrato");
}

/* 2) Mapa producto -> archivo REAL */
$files = [
  'prestacion_servicios'   => 'contratoPRESTACIONDESERVICIOS.html',
  'entrega_express'        => 'contratoPRESTACIONSERVICIOSEX.html', // OJO: este nombre es raro, pero existe
  'licencia_temporal'      => 'contratoLICENCIATEMPORAL.HTML',
  'branding_diseno'        => 'contratoBRANDING.HTML',
  'freelance'              => 'contratoFRELANCE.HTML',
  'colaboracion'           => 'contratoCOLABORACIONES.HTML',
  'obra_por_encargo'       => 'contratoOBRAPORENCARGO.HTML',
  'cesion_derechos'        => 'contratoCESIONDEDERECHOS.HTML',
  'terminacion_anticipada' => 'contratoTERMINACION.HTML',
];

if (!isset($files[$product])) {
  http_response_code(404);
  die("Archivo no mapeado para este producto");
}

$filePath = __DIR__ . DIRECTORY_SEPARATOR . $files[$product];

if (!file_exists($filePath)) {
  http_response_code(404);
  die("Archivo no existe: " . $files[$product]);
}

/* 3) Servir archivo */
$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

if ($ext === 'pdf') {
  header('Content-Type: application/pdf');
} else {
  header('Content-Type: text/html; charset=utf-8');
}

header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
readfile($filePath);
exit;
