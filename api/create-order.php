<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$product = $data['product'] ?? null;

$catalog = require __DIR__ . '/../config/catalog.php';


if (!$product || !isset($catalog[$product])) {
  http_response_code(400);
  echo json_encode(['error' => 'Producto inválido']);
  exit;
}

$amount = $catalog[$product]['price'];
$title  = $catalog[$product]['title'];

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

$accessToken = getAccessToken();

$orderData = [
  "intent" => "CAPTURE",
  "purchase_units" => [[
    "reference_id" => "PU1",
    "description"  => $title,
    "custom_id"    => $product,
    "amount" => [
      "currency_code" => "MXN",
      "value" => $amount
    ]
  ]],
  "application_context" => [
    "shipping_preference" => "NO_SHIPPING"
  ]
];

$ch = curl_init();
curl_setopt_array($ch, [
  CURLOPT_URL => PAYPAL_BASE . "/v2/checkout/orders",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => [
    "Content-Type: application/json",
    "Authorization: Bearer $accessToken"
  ],
  CURLOPT_POSTFIELDS => json_encode($orderData),
]);

$response = curl_exec($ch);
$errno = curl_errno($ch);
$err  = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($errno) {
  http_response_code(500);
  echo json_encode(['error' => "curl order: $err"]);
  exit;
}

$json = json_decode($response, true);
if ($code < 200 || $code >= 300 || empty($json['id'])) {
  http_response_code(500);
  echo json_encode(['error' => 'Create order failed', 'paypal' => $json, 'http' => $code]);
  exit;
}

echo json_encode(['id' => $json['id']]);
