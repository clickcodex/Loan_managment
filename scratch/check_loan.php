<?php
$pdo = new PDO('mysql:host=localhost', 'root', '');
$dbs = $pdo->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN);
foreach ($dbs as $db) {
    if (in_array($db, ['information_schema', 'mysql', 'performance_schema', 'phpmyadmin'])) continue;
    try {
        $p = new PDO("mysql:host=localhost;dbname=$db", 'root', '');
        $tables = $p->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $t) {
            if ($t === 'loans' || $t === 'customers' || $t === 'loan_ledger') {
                $count = $p->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
                if ($count > 0) {
                    $rows = $p->query("SELECT * FROM `$t` WHERE 1")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($rows as $r) {
                        $str = json_encode($r);
                        if (stripos($str, 'ashok') !== false || stripos($str, 'patidar') !== false || stripos($str, '10150') !== false || stripos($str, '5150') !== false) {
                            echo "Found match in $db.$t:\n";
                            print_r($r);
                            break 2;
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {}
}
