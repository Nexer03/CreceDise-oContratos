<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

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

$catalog = require __DIR__ . '/../config/catalog.php';

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

/* ================= EMAIL FUNCTION ================= */
function sendAdminPaymentEmail(array $pay): void {
  $adminEmail = getenv('ADMIN_EMAIL') ?: '';
  if ($adminEmail === '') return;

  $host = getenv('SMTP_HOST') ?: '';
  $user = getenv('SMTP_USER') ?: '';
  $pass = getenv('SMTP_PASS') ?: '';
  $port = (int)(getenv('SMTP_PORT') ?: 587);

  if ($host === '' || $user === '' || $pass === '') return;

  $mail = new PHPMailer(true);
  $mail->isSMTP();
  $mail->Host = $host;
  $mail->SMTPAuth = true;
  $mail->Username = $user;
  $mail->Password = $pass;
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port = $port;

  $fromName = getenv('SMTP_FROM_NAME') ?: 'Crece Diseño';
  $mail->setFrom($user, $fromName);
  $mail->addAddress($adminEmail);

  $mail->isHTML(true);
  $mail->Subject = "Pago COMPLETADO: {$pay['product']} - {$pay['amount']} {$pay['currency']}";

  $mail->Body = "
    <h3>Nuevo pago completado</h3>
    <ul>
      <li><b>Producto:</b> {$pay['product']}</li>
      <li><b>Monto:</b> {$pay['amount']} {$pay['currency']}</li>
      <li><b>Order ID:</b> {$pay['order_id']}</li>
      <li><b>Payer:</b> {$pay['payer_email']}</li>
      <li><b>Usuario ID:</b> {$pay['usuario_id']}</li>
      <li><b>Fecha:</b> {$pay['created_at']}</li>
    </ul>
  ";

  $mail->AltBody = "Pago completado: {$pay['product']} {$pay['amount']} {$pay['currency']} | Order: {$pay['order_id']}";

  $mail->send();
}

/* ================= PAYPAL ================= */
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
  curl_close($ch);

  $json = json_decode($response, true);
  return $json['access_token'] ?? null;
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
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  return [$code, json_decode($response, true)];
}

$accessToken = getAccessToken();

/* ================= CAPTURE ================= */
[$capCode, $capJson] = paypalRequest(
  "POST",
  PAYPAL_BASE . "/v2/checkout/orders/$orderID/capture",
  $accessToken
);

if ($capCode < 200 || $capCode >= 300) {
  http_response_code(500);
  echo json_encode(['error' => 'Capture failed']);
  exit;
}

/* ================= GET ORDER ================= */
[$getCode, $orderJson] = paypalRequest(
  "GET",
  PAYPAL_BASE . "/v2/checkout/orders/$orderID",
  $accessToken
);

$status = $capJson['status'] ?? null;
$pu = $orderJson['purchase_units'][0] ?? [];
$customId = $pu['custom_id'] ?? null;
$captureData = $capJson['purchase_units'][0]['payments']['captures'][0] ?? [];

$amount = $captureData['amount']['value'] ?? null;
$currency = $captureData['amount']['currency_code'] ?? null;
$expected = $catalog[$product]['price'];

if ($status !== 'COMPLETED') {
  http_response_code(400);
  echo json_encode(['error' => 'Pago no completado']);
  exit;
}
if ($customId !== $product) {
  http_response_code(400);
  echo json_encode(['error' => 'Producto no coincide']);
  exit;
}
if ($currency !== 'MXN' || (string)$amount !== (string)$expected) {
  http_response_code(400);
  echo json_encode(['error' => 'Monto no coincide']);
  exit;
}

/* ================= GUARDAR EN DB ================= */
$payerEmail = $orderJson['payer']['email_address'] ?? null;

try {
  $stmt = $pdo->prepare("
    INSERT INTO payments (usuario_id, order_id, payer_email, amount, currency, status, product, admin_notified)
    VALUES (:usuario_id, :order_id, :payer_email, :amount, :currency, :status, :product, 0)
  ");

  $stmt->execute([
    ':usuario_id' => $usuarioId,
    ':order_id' => $orderID,
    ':payer_email' => $payerEmail,
    ':amount' => $amount,
    ':currency' => $currency,
    ':status' => $status,
    ':product' => $product,
  ]);

} catch (PDOException $e) {
  if ($e->getCode() !== '23000') {
    http_response_code(500);
    echo json_encode(['error' => 'DB insert failed']);
    exit;
  }
}

/* ================= NOTIFICAR ADMIN ================= */
$st = $pdo->prepare("
  SELECT * FROM payments
  WHERE order_id = :oid
  LIMIT 1
");
$st->execute([':oid' => $orderID]);
$payRow = $st->fetch(PDO::FETCH_ASSOC);

if ($payRow && (int)$payRow['admin_notified'] === 0) {
  try {
    sendAdminPaymentEmail($payRow);

    $upd = $pdo->prepare("UPDATE payments SET admin_notified = 1 WHERE id = :id");
    $upd->execute([':id' => (int)$payRow['id']]);
  } catch (Throwable $e) {
    // No romper la compra si falla el correo
  }
}

/* ================= OK ================= */
echo json_encode([
  'ok' => true,
  'status' => 'COMPLETED',
  'order_id' => $orderID,
  'product' => $product
]);