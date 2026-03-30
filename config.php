<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'u82414');
define('DB_USER', 'u82414');
define('DB_PASS', '7011793');

define('DEBUG_MODE', false);

ini_set('display_errors', DEBUG_MODE ? 1 : 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');
error_reporting(DEBUG_MODE ? E_ALL : 0);

define('ADMIN_USER', 'admin');
define('ADMIN_PASS_HASH', '$2a$12$oSm4pzQAZ67Jz3h61iwYSu64.3K8K43IKefC9MJDQt2mmJqG8gSyu');

define('SITE_NAME', 'Анкета');
define('COOKIE_EXPIRE', 365 * 24 * 60 * 60);
?>
