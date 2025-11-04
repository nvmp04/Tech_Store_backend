<?php
// JWT Configuration
define('JWT_SECRET_KEY', 'k9$ZB!w2Qp4#x5V@Df3mHu8%Jr6^Tg1*Ys5&Ln0!Pa9$Xe2Qf4#Wc7Vr');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION', 3600); // 1 hour (3600 seconds)

// Password Configuration
define('PASSWORD_MIN_LENGTH', 8);

// Base URL
define('BASE_URL', 'http://localhost/BTL/TechStore');

// Email Configuration (để sau)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'letruongthinh1410@gmail.com');
define('SMTP_PASSWORD', '');
define('SMTP_FROM_EMAIL', 'letruongthinh1410@gmail.com');
define('SMTP_FROM_NAME', 'TechStore');

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Error reporting (tắt trong production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
