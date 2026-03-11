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
  $badge = $forUser ? 'Comprobante' : 'Pago COMPLETADO';
  $subtitle = $forUser ? 'Comprobante de compra' : 'Notificación de pago';
  $headline = $forUser ? '¡Gracias por tu compra!' : 'Nuevo pago completado';

  $supportEmail = getenv('SUPPORT_EMAIL') ?: '';
  $supportWhatsapp = getenv('SUPPORT_WHATSAPP') ?: '';

  $productRaw = (string)($pay['product'] ?? '');
  $productName = ucwords(str_replace('_', ' ', $productRaw));

  $payer = (string)($pay['payer_email'] ?? '');
  $createdAtRaw = (string)($pay['created_at'] ?? '');
  $expiresAtRaw = (string)($pay['access_expires_at'] ?? '');

  $fechaCompra = $createdAtRaw !== '' ? date('d/m/Y H:i', strtotime($createdAtRaw)) : 'No disponible';
  $fechaVigencia = $expiresAtRaw !== '' ? date('d/m/Y H:i', strtotime($expiresAtRaw)) : 'No disponible';

  $hint = $forUser
    ? 'Tu pago fue procesado correctamente. Este correo funciona como comprobante de compra.'
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
    $nombre = trim((string)($userInfo['nombre'] ?? ''));
    $saludo = $nombre !== '' ? ('Hola ' . htmlspecialchars($nombre) . ',') : 'Hola,';

    $contactBlocks = '';

    if ($supportEmail !== '') {
      $contactBlocks .= "
        <div style=\"margin-top:8px;\">
          <b>Correo:</b> ".htmlspecialchars($supportEmail)."
        </div>
      ";
    }

    if ($supportWhatsapp !== '') {
      $contactBlocks .= "
        <div style=\"margin-top:8px;\">
          <b>WhatsApp:</b> ".htmlspecialchars($supportWhatsapp)."
        </div>
      ";
    }

    if ($contactBlocks === '') {
      $contactBlocks = "
        <div style=\"margin-top:8px;\">
          <b>Medio de contacto:</b> pendiente por definir
        </div>
      ";
    }

    $nextSteps = "
      <tr>
        <td style=\"padding:0 24px 18px 24px;\">
          <table role=\"presentation\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\"
                 style=\"background:#ffffff;border:1px solid rgba(0,0,0,0.06);border-radius:14px;overflow:hidden;\">
            <tr>
              <td style=\"padding:14px 16px;font-family:Montserrat, Arial, sans-serif;color:#1A1C36;\">
                <div style=\"font-size:12px;letter-spacing:0.3px;text-transform:uppercase;opacity:0.7;margin-bottom:6px;\">Información importante</div>
                <div style=\"font-size:14px;line-height:1.7;opacity:0.92;\">
                  <div style=\"margin-bottom:10px;\">$saludo</div>

                  <div style=\"margin-bottom:10px;\">
                    Tu contrato <b>".htmlspecialchars($productName)."</b> estará disponible hasta el
                    <b>".htmlspecialchars($fechaVigencia)."</b>.
                  </div>

                  <div style=\"margin-bottom:10px;\">
                    Es importante que este contrato sea llenado con la asesoría de un <b>abogado</b>,
                    para asegurarte de usarlo correctamente según tu caso.
                  </div>

                  <div style=\"margin-bottom:10px;\">
                    Para recibir orientación, deberás contactar por alguno de los siguientes medios:
                  </div>

                  $contactBlocks
                </div>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    ";
  }

  $html = '
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

        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:600px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,0.10);">

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
                          '.htmlspecialchars($productName).'
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
                        <td style="padding:8px 0;border-top:1px solid rgba(0,0,0,0.06);opacity:0.85;"><b>Fecha de compra</b></td>
                        <td style="padding:8px 0;border-top:1px solid rgba(0,0,0,0.06);">
                          '.htmlspecialchars($fechaCompra).'
                        </td>
                      </tr>';

  if ($forUser) {
    $html .= '
                      <tr>
                        <td style="padding:8px 0;border-top:1px solid rgba(0,0,0,0.06);opacity:0.85;"><b>Válido hasta</b></td>
                        <td style="padding:8px 0;border-top:1px solid rgba(0,0,0,0.06);">
                          '.htmlspecialchars($fechaVigencia).'
                        </td>
                      </tr>';
  }

  $html .= '
                    </table>

                  </td>
                </tr>
              </table>
            </td>
          </tr>

          '.$nextSteps.'

          <tr>
            <td style="padding:16px 24px;background:#ffffff;border-top:1px solid rgba(0,0,0,0.06);">
              <div style="font-family:Montserrat, Arial, sans-serif;font-size:12px;color:#1A1C36;opacity:0.65;line-height:1.5;">
                Este correo fue generado automáticamente por '.$brand.'.<br>';

  if ($forUser) {
    $html .= '
                <span style="opacity:0.8;">Conserva este correo como comprobante de compra.</span>';
  } else {
    $html .= '
                <span style="opacity:0.8;">Notificación interna del sistema.</span>';
  }

  $html .= '
              </div>
            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>
</body>
</html>';

  return $html;
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
  $mail->CharSet = 'UTF-8';
  $mail->Encoding = 'base64';
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

  $webEmail = trim((string)($userInfo['correo'] ?? ''));
  $paypalEmail = trim((string)($pay['payer_email'] ?? ''));

  $mail = new PHPMailer(true);
  $mail->CharSet = 'UTF-8';
  $mail->Encoding = 'base64';
  $mail->isSMTP();
  $mail->Host = $host;
  $mail->SMTPAuth = true;
  $mail->Username = $user;
  $mail->Password = $pass;
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port = $port;

  $fromName = getenv('SMTP_FROM_NAME') ?: 'Crece Diseño';
  $mail->setFrom($user, $fromName);

  $added = [];

  if ($webEmail !== '' && filter_var($webEmail, FILTER_VALIDATE_EMAIL)) {
    $mail->addAddress($webEmail);
    $added[strtolower($webEmail)] = true;
  }

  if (
    $paypalEmail !== '' &&
    filter_var($paypalEmail, FILTER_VALIDATE_EMAIL) &&
    !isset($added[strtolower($paypalEmail)])
  ) {
    $mail->addAddress($paypalEmail);
    $added[strtolower($paypalEmail)] = true;
  }

  if (empty($added)) {
    return;
  }

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
      u.correo AS correo,
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
    INSERT INTO payments (
      usuario_id,
      order_id,
      payer_email,
      amount,
      currency,
      status,
      product,
      created_at,
      access_expires_at,
      access_status,
      admin_notified,
      user_notified
    )
    VALUES (
      :usuario_id,
      :order_id,
      :payer_email,
      :amount,
      :currency,
      :status,
      :product,
      NOW(),
      DATE_ADD(NOW(), INTERVAL 7 DAY),
      'active',
      0,
      0
    )
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
    if (!empty($payRow['amount']) && !empty($payRow['currency']) && !empty($payRow['product'])) {
      $userInfo = resolveUserInfo($pdo, $payRow);
      if (!empty($payRow['payer_email']) || !empty($userInfo['correo'])) {
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
