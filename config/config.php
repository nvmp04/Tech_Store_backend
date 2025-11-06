
<?php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/auto_init.php';
// JWT
define('JWT_SECRET_KEY', $_ENV['JWT_SECRET_KEY']);
define('JWT_ALGORITHM', $_ENV['JWT_ALGORITHM']);
define('JWT_EXPIRATION', (int)$_ENV['JWT_EXPIRATION']);

// PASSWORD
define('PASSWORD_MIN_LENGTH', (int)$_ENV['PASSWORD_MIN_LENGTH']);

// BASE URL
define('BASE_URL', $_ENV['BASE_URL']);

// EMAIL
define('SMTP_HOST', $_ENV['SMTP_HOST']);
define('SMTP_PORT', (int)$_ENV['SMTP_PORT']);
define('SMTP_USERNAME', $_ENV['SMTP_USERNAME']);
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD']);
define('SMTP_FROM_EMAIL', $_ENV['SMTP_FROM_EMAIL']);
define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME']);


// Timezone
date_default_timezone_set($_ENV['TIMEZONE']);

// Error reporting (tắt trong production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
