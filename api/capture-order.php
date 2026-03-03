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
function buildPaymentEmailHtml(array $pay, array $userInfo, bool $forUser): string {
  $brand = 'Crece Diseño';
  $badge = $forUser ? ' Comprobante' : ' Pago COMPLETADO';
  $subtitle = $forUser ? 'Comprobante de compra' : 'Notificación de pago';
  $headline = $forUser ? '¡Gracias por tu compra!' : 'Nuevo pago completado';
  $hint = $forUser
    ? 'Guarda este correo como comprobante. Si necesitas ayuda, responde a este correo o contáctanos.'
    : 'Se registró un pago exitoso en el catálogo.';

  $maybeUserRow = '';
  if (!$forUser) {
    $maybeUserRow = "
      <tr>
        <td style=\"padding:8px 0;border-top:1px solid rgba(0,0,0,0.06);opacity:0.85;\"><b>Usuario ID</b></td>
        <td style=\"padding:8px 0;border-top:1px solid rgba(0,0,0,0.06);\">
          ".htmlspecialchars((string)($pay['usuario_id'] ?? ''))."
        </td>
      </tr>
    ";
  }

  $nextSteps = '';
  if ($forUser) {
    $nombre = (string)($userInfo['nombre'] ?? '');
    $saludo = $nombre !== '' ? ('Hola ' . htmlspecialchars($nombre) . ',') : 'Hola,';

    $nextSteps = "
      <tr>
        <td style=\"padding:0 24px 18px 24px;\">
          <table role=\"presentation\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\"
                 style=\"background:#ffffff;border:1px solid rgba(0,0,0,0.06);border-radius:14px;overflow:hidden;\">
            <tr>
              <td style=\"padding:14px 16px;font-family:Montserrat, Arial, sans-serif;color:#1A1C36;\">
                <div style=\"font-size:12px;letter-spacing:0.3px;text-transform:uppercase;opacity:0.7;margin-bottom:6px;\">Qué sigue</div>
                <div style=\"font-size:14px;line-height:1.7;opacity:0.88;\">
                  <div style=\"margin-bottom:8px;\">$saludo</div>
                  <ul style=\"margin:0;padding-left:18px;\">
                    <li>Accede a tu cuenta para ver tu compra / contenido.</li>
                    <li>Si pagaste y no ves acceso en unos minutos, contáctanos con tu <b>Order ID</b>.</li>
                  </ul>
                </div>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    ";
  }

  $payer = (string)($pay['payer_email'] ?? '');

  return '
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width">
  <title>Pago completado</title>
</head>
<body style="margin:0;padding:0;background:#F8F9FA;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F8F9FA;padding:24px 12px;">
    <tr>
      <td align="center">

        <!-- Container -->
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:600px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,0.10);">

          <!-- Header (gradient) -->
          <tr>
            <td style="padding:22px 24px;background:#5B4393;background-image:linear-gradient(135deg,#667eea 0%,#764ba2 50%,#5B4393 100%);">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="color:#ffffff;font-family:Quicksand, Arial, sans-serif;">
                    <div style="font-size:18px;font-weight:700;letter-spacing:0.2px;">'.$brand.'</div>
                    <div style="font-size:13px;opacity:0.92;margin-top:4px;">'.htmlspecialchars($subtitle).'</div>
                  </td>
                  <td align="right" style="font-family:Montserrat, Arial, sans-serif;">
                    <span style="display:inline-block;background:rgba(255,255,255,0.18);border:1px solid rgba(255,255,255,0.28);color:#ffffff;padding:6px 10px;border-radius:999px;font-size:12px;">
                      '.htmlspecialchars($badge).'
                    </span>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:22px 24px 8px 24px;">
              <div style="font-family:Quicksand, Arial, sans-serif;font-size:18px;font-weight:700;color:#1A1C36;margin:0 0 6px 0;">
                '.htmlspecialchars($headline).'
              </div>
              <div style="font-family:Montserrat, Arial, sans-serif;font-size:14px;color:#1A1C36;opacity:0.85;line-height:1.6;">
                '.htmlspecialchars($hint).'
              </div>
            </td>
          </tr>

          <!-- Summary card -->
          <tr>
            <td style="padding:0 24px 18px 24px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                     style="background:#F8F9FA;border:1px solid rgba(33,124,227,0.18);border-radius:14px;overflow:hidden;">
                <tr>
                  <td style="padding:16px 16px 10px 16px;font-family:Montserrat, Arial, sans-serif;color:#1A1C36;">
                    <div style="font-size:12px;letter-spacing:0.3px;text-transform:uppercase;opacity:0.7;margin-bottom:6px;">
                      Detalles
                    </div>

                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                      <tr>
                        <td style="padding:8px 0;border-top:1px solid rgba(0,0,0,0.06);width:38%;opacity:0.85;"><b>Producto</b></td>
                        <td style="padding:8px 0;border-top:1px solid rgba(0,0,0,0.06);">
                          '.htmlspecialchars((string)($pay['product'] ?? '')).'
                        </td>
                      </tr>
                      <tr>
                        <td style="padding:8px 0;border-top:1px solid rgba(0,0,0,0.06);opacity:0.85;"><b>Monto</b></td>
                        <td style="padding:8px 0;border-top:1px solid rgba(0,0,0,0.06);">
                          <span style="display:inline-block;background:rgba(33,124,227,0.10);color:#217CE3;border:1px solid rgba(33,124,227,0.25);padding:4px 10px;border-radius:999px;font-weight:700;">
                            '.htmlspecialchars((string)($pay['amount'] ?? '')).' '.htmlspecialchars((string)($pay['currency'] ?? '')).'
                          </span>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding:8px 0;border-top:1px solid rgba(0,0,0,0.06);opacity:0.85;"><b>Order ID</b></td>
                        <td style="padding:8px 0;border-top:1px solid rgba(0,0,0,0.06);font-family:ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;">
                          '.htmlspecialchars((string)($pay['order_id'] ?? '')).'
                        </td>
                      </tr>
                      <tr>
                        <td style="padding:8px 0;border-top:1px solid rgba(0,0,0,0.06);opacity:0.85;"><b>Payer</b></td>
                        <td style="padding:8px 0;border-top:1px solid rgba(0,0,0,0.06);">
                          '.htmlspecialchars($payer).'
                        </td>
                      </tr>
                      '.$maybeUserRow.'
                      <tr>
                        <td style="padding:8px 0;border-top:1px solid rgba(0,0,0,0.06);opacity:0.85;"><b>Fecha</b></td>
                        <td style="padding:8px 0;border-top:1px solid rgba(0,0,0,0.06);">
                          '.htmlspecialchars((string)($pay['created_at'] ?? '')).'
                        </td>
                      </tr>
                    </table>

                  </td>
                </tr>
              </table>
            </td>
          </tr>

          '.$nextSteps.'

          <!-- Footer -->
          <tr>
            <td style="padding:16px 24px;background:#ffffff;border-top:1px solid rgba(0,0,0,0.06);">
              <div style="font-family:Montserrat, Arial, sans-serif;font-size:12px;color:#1A1C36;opacity:0.65;line-height:1.5;">
                Este correo fue generado automáticamente por '.$brand.'.<br>
                <span style="opacity:0.8;">No respondas a este mensaje.</span>
              </div>
            </td>
          </tr>

        </table>
        <!-- /Container -->

      </td>
    </tr>
  </table>
</body>
</html>
';
}

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
  $mail->Body = buildPaymentEmailHtml($pay, ['nombre' => ''], false);

  $mail->AltBody =
    "Pago COMPLETADO | Producto: {$pay['product']} | Monto: {$pay['amount']} {$pay['currency']} | Order: {$pay['order_id']} | Payer: {$pay['payer_email']} | Usuario: {$pay['usuario_id']} | Fecha: {$pay['created_at']}";

  $mail->send();
}

function sendUserReceiptEmail(array $pay, array $userInfo): void {
  $host = getenv('SMTP_HOST') ?: '';
  $user = getenv('SMTP_USER') ?: '';
  $pass = getenv('SMTP_PASS') ?: '';
  $port = (int)(getenv('SMTP_PORT') ?: 587);

  if ($host === '' || $user === '' || $pass === '') return;

  $to = (string)($userInfo['correo'] ?? '');
  if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) return;

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
  $mail->addAddress($to);

  // Mejor entregabilidad / soporte
  $support = getenv('SUPPORT_EMAIL') ?: $user;
  if (filter_var($support, FILTER_VALIDATE_EMAIL)) {
    $mail->addReplyTo($support, $fromName . ' Soporte');
  }

  $mail->isHTML(true);
  $mail->Subject = "Comprobante: {$pay['product']} - {$pay['amount']} {$pay['currency']}";
  $mail->Body = buildPaymentEmailHtml($pay, $userInfo, true);

  $mail->AltBody =
    "Comprobante | Producto: {$pay['product']} | Monto: {$pay['amount']} {$pay['currency']} | Order: {$pay['order_id']} | Fecha: {$pay['created_at']}";

  $mail->send();
}

function resolveUserInfo(PDO $pdo, array $payRow): array {
  $pid = (int)($payRow['id'] ?? 0);
  if ($pid <= 0) return ['correo' => '', 'nombre' => ''];

  $st = $pdo->prepare("
    SELECT
      COALESCE(NULLIF(TRIM(p.payer_email), ''), u.correo) AS correo,
      u.nombre AS nombre
    FROM payments p
    JOIN usuarios u ON u.id = p.usuario_id
    WHERE p.id = :pid
    LIMIT 1
  ");
  $st->execute([':pid' => $pid]);
  $row = $st->fetch(PDO::FETCH_ASSOC);

  return [
    'correo' => (string)($row['correo'] ?? ''),
    'nombre' => (string)($row['nombre'] ?? ''),
  ];
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

/* ================= NOTIFICAR USUARIO ================= */
if ($payRow && (int)$payRow['user_notified'] === 0) {
  try {
    // Evita mandar “comprobantes” incompletos
    if (!empty($payRow['amount']) && !empty($payRow['currency']) && !empty($payRow['product'])) {
      $userInfo = resolveUserInfo($pdo, $payRow);
      if (!empty($userInfo['correo'])) {
        sendUserReceiptEmail($payRow, $userInfo);

        $upd = $pdo->prepare("UPDATE payments SET user_notified = 1 WHERE id = :id");
        $upd->execute([':id' => (int)$payRow['id']]);
      }
    }
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