<?php
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
foreach ($_ENV as $k => $v) putenv("$k=$v");

$cols = App\Config\Database::fetchAll('SHOW COLUMNS FROM customers');
echo json_encode(array_column($cols, 'Field'), JSON_PRETTY_PRINT);
