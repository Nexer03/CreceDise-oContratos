<?php
require_once '../config/config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$amount = $data['amount'] ?? '0.00';

function getAccessToken() {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, PAYPAL_BASE . "/v1/oauth2/token");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, PAYPAL_CLIENT_ID . ":" . PAYPAL_CLIENT_SECRET);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

    $headers = [
        "Accept: application/json",
        "Accept-Language: en_US"
    ];

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($response, true);

    return $json['access_token'];
}

$accessToken = getAccessToken();

$orderData = [
    "intent" => "CAPTURE",
    "purchase_units" => [[
        "amount" => [
            "currency_code" => "MXN",
            "value" => $amount
        ]
    ]]
];

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, PAYPAL_BASE . "/v2/checkout/orders");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $accessToken"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));

$response = curl_exec($ch);
curl_close($ch);

echo $response;
