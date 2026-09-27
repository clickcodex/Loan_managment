<?php
require_once __DIR__ . '/../app/Config/Database.php';
$p = App\Config\Database::connect();
echo $p->query("SELECT VERSION()")->fetchColumn() . "\n";
