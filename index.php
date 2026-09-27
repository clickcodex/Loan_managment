<?php
require_once __DIR__ . '/vendor/autoload.php';
 $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
 $dotenv->load();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

foreach ($_ENV as $key => $value) {
    putenv("$key=$value");
}

/* =============================================================
   TEMPORARY FIX FOR LOCALHOST REDIRECTS
   -------------------------------------------------------------
   When running on localhost, absolute URLs like "/login"
   redirect to http://localhost/login instead of your project folder.
   This block automatically detects your local base path and fixes it.
   -------------------------------------------------------------
   Remove or comment this block when you move project online.
   ============================================================= */
$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$basePath  = ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') ? '' : rtrim(str_replace('\\', '/', $scriptDir), '/');

if (strpos($host, 'localhost') !== false) {
    define('BASE_URL', $protocol . '://' . $host . $basePath);
} else {
    // On live server: works automatically on root domain ('') or subfolder ('/subfolder')
    define('BASE_URL', $basePath);
}
require_once __DIR__ . '/app/routes.php';
?>