<?php

$envPath = __DIR__ . '/../.env';

if (!file_exists($envPath)) {
  http_response_code(500);
  die("ERROR: .env no existe en: $envPath");
}

if (!is_readable($envPath)) {
  http_response_code(500);
  die("ERROR: .env no es legible (permisos) en: $envPath");
}

$env = parse_ini_file($envPath, false, INI_SCANNER_RAW);

if ($env === false) {
  http_response_code(500);
  die("ERROR: parse_ini_file() no pudo leer/parsear .env. Revisa formato.");
}

/* =====================================================
   Exportar TODO el .env para que getenv() funcione
   ===================================================== */
foreach ($env as $key => $value) {

  $key = trim($key);
  if ($key === '') continue;

  // limpiar comillas
  $cleanValue = trim($value, "\"' ");

  // hacer disponible en entorno
  putenv("$key=$cleanValue");
  $_ENV[$key] = $cleanValue;
  $_SERVER[$key] = $cleanValue;
}

/* =====================================================
   VALIDACIÓN PAYPAL (obligatorio)
   ===================================================== */
foreach (['PAYPAL_CLIENT_ID','PAYPAL_CLIENT_SECRET','PAYPAL_MODE'] as $k) {
  if (!isset($env[$k]) || trim($env[$k]) === '') {
    http_response_code(500);
    die("ERROR: Falta la variable $k en .env");
  }
}

define('PAYPAL_CLIENT_ID', getenv('PAYPAL_CLIENT_ID'));
define('PAYPAL_CLIENT_SECRET', getenv('PAYPAL_CLIENT_SECRET'));
define('PAYPAL_MODE', getenv('PAYPAL_MODE'));

/* =====================================================
   BASE URL PAYPAL
   ===================================================== */
define('PAYPAL_BASE', PAYPAL_MODE === 'live'
  ? 'https://api-m.paypal.com'
  : 'https://api-m.sandbox.paypal.com'
);