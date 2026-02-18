<?php

$env = parse_ini_file(__DIR__ . '/../.env');

define('PAYPAL_CLIENT_ID', $env['PAYPAL_CLIENT_ID']);
define('PAYPAL_CLIENT_SECRET', $env['PAYPAL_CLIENT_SECRET']);
define('PAYPAL_MODE', $env['PAYPAL_MODE']);

define('PAYPAL_BASE', PAYPAL_MODE === 'live'
    ? 'https://api-m.paypal.com'
    : 'https://api-m.sandbox.paypal.com'
);
