<?php

require_once __DIR__ . '/../app/Config/Database.php';

$cookieFile = __DIR__ . '/cookie_verify.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/TransactionManagement/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$loginPage = curl_exec($ch);

preg_match('/name=["\']csrf_token["\']\s+value=["\']([^"\']+)["\']/', $loginPage, $m);
$csrf = $m[1] ?? '';

curl_setopt($ch, CURLOPT_URL, 'http://localhost/TransactionManagement/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'csrf_token' => $csrf,
    'username'   => 'admin',
    'password'   => 'admin123'
]));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_exec($ch);

// Fetch Loan 44 (Running Loan with collaterals from screenshot)
curl_setopt($ch, CURLOPT_URL, 'http://localhost/TransactionManagement/loans/44');
curl_setopt($ch, CURLOPT_POST, false);
$html44 = curl_exec($ch);

echo "=== LOAN 44 (ACTIVE/RUNNING LOAN: LMS-2026-0044) ===\n";
echo "1. Contains yellow 'Deliver Jewellery' button: " . (strpos($html44, 'Deliver Jewellery</button>') !== false ? "YES (ERROR)" : "NO (CORRECT)") . "\n";
echo "2. Contains 'jewellery_delivered' checkbox in modal: " . (strpos($html44, 'name="jewellery_delivered"') !== false ? "YES (ERROR)" : "NO (CORRECT)") . "\n";
echo "3. Contains 'deliverModal': " . (strpos($html44, 'deliverModal') !== false ? "YES (ERROR)" : "NO (CORRECT)") . "\n";
echo "4. Contains 'Deliver Jewellery to Customer & Free Up Rack Slots': " . (strpos($html44, 'Deliver Jewellery to Customer & Free Up Rack Slots') !== false ? "YES (ERROR)" : "NO (CORRECT)") . "\n";

// Fetch Loan 14 (Closed Loan: LMS-2026-0006)
curl_setopt($ch, CURLOPT_URL, 'http://localhost/TransactionManagement/loans/14');
$html14 = curl_exec($ch);

echo "\n=== LOAN 14 (CLOSED LOAN: LMS-2026-0006) ===\n";
echo "1. Contains green 'Jewellery Delivered' badge: " . (strpos($html14, 'Jewellery Delivered') !== false ? "YES (CORRECT)" : "NO (ERROR)") . "\n";
echo "2. Contains yellow 'Deliver Jewellery' button: " . (strpos($html14, 'Deliver Jewellery</button>') !== false ? "YES (ERROR)" : "NO (CORRECT)") . "\n";
echo "3. Contains 'jewellery_delivered' checkbox in modal: " . (strpos($html14, 'name="jewellery_delivered"') !== false ? "YES (ERROR)" : "NO (CORRECT)") . "\n";

// Fetch Payments Index
curl_setopt($ch, CURLOPT_URL, 'http://localhost/TransactionManagement/payments');
$htmlPayments = curl_exec($ch);
echo "\n=== PAYMENTS INDEX ===\n";
echo "1. Contains 'jewellery_delivered' checkbox in modal: " . (strpos($htmlPayments, 'name="jewellery_delivered"') !== false ? "YES (ERROR)" : "NO (CORRECT)") . "\n";

// Fetch Dues Index
curl_setopt($ch, CURLOPT_URL, 'http://localhost/TransactionManagement/dues');
$htmlDues = curl_exec($ch);
echo "\n=== DUES INDEX ===\n";
echo "1. Contains 'jewellery_delivered' checkbox in modal: " . (strpos($htmlDues, 'name="jewellery_delivered"') !== false ? "YES (ERROR)" : "NO (CORRECT)") . "\n";

if (file_exists($cookieFile)) unlink($cookieFile);
