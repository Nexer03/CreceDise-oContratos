<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'No autenticado']);
  exit;
}
$usuarioId = (int)$_SESSION['usuario_id'];

$data = json_decode(file_get_contents("php://input"), true);
$orderID = $data['orderID'] ?? null;
$product = $data['product'] ?? null;

$catalog = require __DIR__ . '/../config/catalog.php'; // crea este archivo (si aún no)

if (!$orderID) {
  http_response_code(400);
  echo json_encode(["error" => "No orderID"]);
  exit;
}
if (!$product || !isset($catalog[$product])) {
  http_response_code(400);
  echo json_encode(["error" => "Producto inválido"]);
  exit;
}

/* ===== Access Token ===== */
function getAccessToken() {
  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => PAYPAL_BASE . "/v1/oauth2/token",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD => PAYPAL_CLIENT_ID . ":" . PAYPAL_CLIENT_SECRET,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => "grant_type=client_credentials",
    CURLOPT_HTTPHEADER => ["Accept: application/json", "Accept-Language: en_US"],
  ]);

  $response = curl_exec($ch);
  $errno = curl_errno($ch);
  $err  = curl_error($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($errno) {
    http_response_code(500);
    echo json_encode(['error' => "curl oauth: $err"]);
    exit;
  }

  $json = json_decode($response, true);
  if ($code < 200 || $code >= 300 || empty($json['access_token'])) {
    http_response_code(500);
    echo json_encode(['error' => 'OAuth failed', 'paypal' => $json, 'http' => $code]);
    exit;
  }

  return $json['access_token'];
}

function paypalRequest($method, $url, $accessToken) {
  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_HTTPHEADER => [
      "Content-Type: application/json",
      "Authorization: Bearer $accessToken"
    ],
  ]);

  $response = curl_exec($ch);
  $errno = curl_errno($ch);
  $err  = curl_error($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($errno) {
    return [500, ['error' => "curl: $err"]];
  }
  return [$code, json_decode($response, true)];
}

$accessToken = getAccessToken();

/* ===== 1) CAPTURE ===== */
[$capCode, $capJson] = paypalRequest(
  "POST",
  PAYPAL_BASE . "/v2/checkout/orders/$orderID/capture",
  $accessToken
);

if ($capCode < 200 || $capCode >= 300) {
  http_response_code(500);
  echo json_encode(['error' => 'Capture failed', 'paypal' => $capJson, 'http' => $capCode]);
  exit;
}

/* ===== 2) GET ORDER ===== */
[$getCode, $orderJson] = paypalRequest(
  "GET",
  PAYPAL_BASE . "/v2/checkout/orders/$orderID",
  $accessToken
);

if ($getCode < 200 || $getCode >= 300) {
  http_response_code(500);
  echo json_encode(['error' => 'Get order failed', 'paypal' => $orderJson, 'http' => $getCode]);
  exit;
}

/* ===== Validaciones ===== */
$status = $capJson['status'] ?? null;

$pu = $orderJson['purchase_units'][0] ?? [];
$customId = $pu['custom_id'] ?? null;

$captureData = $capJson['purchase_units'][0]['payments']['captures'][0] ?? [];
$amount = $captureData['amount']['value'] ?? null;
$currency = $captureData['amount']['currency_code'] ?? null;

$expected = $catalog[$product]['price'];

if ($status !== 'COMPLETED') {
  http_response_code(400);
  echo json_encode(['error' => 'Pago no completado', 'status' => $status]);
  exit;
}
if ($customId !== $product) {
  http_response_code(400);
  echo json_encode(['error' => 'Producto no coincide', 'custom_id' => $customId, 'product' => $product]);
  exit;
}
if ($currency !== 'MXN' || (string)$amount !== (string)$expected) {
  http_response_code(400);
  echo json_encode(['error' => 'Monto/moneda no coincide', 'got' => [$currency, $amount], 'expected' => ['MXN', $expected]]);
  exit;
}

/* ===== Guardar en DB ===== */
$payerEmail = $orderJson['payer']['email_address'] ?? null;

try {
  $stmt = $pdo->prepare("
    INSERT INTO payments (usuario_id, order_id, payer_email, amount, currency, status, product)
    VALUES (:usuario_id, :order_id, :payer_email, :amount, :currency, :status, :product)
  ");

  $stmt->execute([
    ':usuario_id' => $usuarioId,
    ':order_id' => $orderID,
    ':payer_email' => $payerEmail,
    ':amount' => $amount,      // DECIMAL ok como string
    ':currency' => $currency,
    ':status' => $status,
    ':product' => $product,
  ]);

} catch (PDOException $e) {
  // Si es duplicado por UNIQUE(order_id), lo ignoramos (pago ya registrado)
  if ($e->getCode() !== '23000') {
    http_response_code(500);
    echo json_encode(['error' => 'DB insert failed', 'db' => $e->getMessage()]);
    exit;
  }
}

/* ===== OK ===== */
echo json_encode([
  'ok' => true,
  'status' => 'COMPLETED',
  'order_id' => $orderID,
  'product' => $product
]);
