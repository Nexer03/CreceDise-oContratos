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
/* 1) Validar compra vigente */
$stmt = $pdo->prepare("
  SELECT id, access_expires_at
  FROM payments
  WHERE usuario_id = :usuario_id
    AND product = :product
    AND status = 'COMPLETED'
    AND access_status = 'active'
    AND access_expires_at IS NOT NULL
    AND access_expires_at > NOW()
  ORDER BY access_expires_at DESC
  LIMIT 1
");
$stmt->execute([
  ':usuario_id' => $usuarioId,
  ':product' => $product
]);

$compra = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$compra) {
  http_response_code(403);
  die("Tu acceso a este contrato no está disponible o ya venció");
}


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
  'prestacion_servicios'   => 'templates/contratoPRESTACIONDESERVICIOS.html',
  'entrega_express'        => 'templates/contratoPRESTACIONSERVICIOSEX.html', 
  'licencia_temporal'      => 'templates/contratoLICENCIATEMPORAL.HTML',
  'branding_diseno'        => 'templates/contratoBRANDING.HTML',
  'freelance'              => 'templates/contratoFRELANCE.HTML',
  'colaboracion'           => 'templates/contratoCOLABORACIONES.HTML',
  'obra_por_encargo'       => 'templates/contratoOBRAPORENCARGO.HTML',
  'cesion_derechos'        => 'templates/contratoCESIONDEDERECHOS.HTML',
  'terminacion_anticipada' => 'templates/contratoTERMINACION.HTML',
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
