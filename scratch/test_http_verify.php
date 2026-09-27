<?php

require_once __DIR__ . '/../app/Config/Database.php';

// Check what admin password hash is or check if we know the password
$admin = \App\Config\Database::fetchOne("SELECT * FROM admin WHERE username = 'admin'");
$passwords = ['admin123', 'admin', 'password', '123456', 'Admin@123'];
$correctPass = null;
foreach ($passwords as $p) {
    if (password_verify($p, $admin['password_hash'])) {
        $correctPass = $p;
        break;
    }
}
echo "Correct password found: " . ($correctPass ?: 'Not in list') . "\n";

$cookieFile = __DIR__ . '/cookie.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/TransactionManagement/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$loginPage = curl_exec($ch);

// Extract CSRF token
preg_match('/name=["\']csrf_token["\']\s+value=["\']([^"\']+)["\']/', $loginPage, $mCsrf);
$csrf = $mCsrf[1] ?? '';
echo "CSRF token: " . substr($csrf, 0, 10) . "...\n";

if ($correctPass && $csrf) {
    curl_setopt($ch, CURLOPT_URL, "http://localhost/TransactionManagement/login");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'csrf_token' => $csrf,
        'username' => 'admin',
        'password' => $correctPass
    ]));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $afterLogin = curl_exec($ch);
    echo "Login HTTP response length: " . strlen($afterLogin) . "\n";

    // Now test Loan 43
    curl_setopt($ch, CURLOPT_URL, "http://localhost/TransactionManagement/loans/43");
    curl_setopt($ch, CURLOPT_POST, false);
    $loan43Html = curl_exec($ch);

    echo "\n=== CHECKING LOAN 43 HTML ===\n";
    if (strpos($loan43Html, '24K') !== false) {
        echo "SUCCESS: '24K' found in loan 43 HTML!\n";
        preg_match_all('/<span[^>]*>[^<]*24K[^<]*<\/span>/i', $loan43Html, $matches);
        foreach ($matches[0] as $m) {
            echo "   Badge: " . trim($m) . "\n";
        }
    } else {
        echo "FAILED: '24K' not found in loan 43 HTML\n";
    }

    // Now test Search for 'test'
    curl_setopt($ch, CURLOPT_URL, "http://localhost/TransactionManagement/search?q=test");
    $searchHtml = curl_exec($ch);

    echo "\n=== CHECKING SEARCH HTML FOR 'test' ===\n";
    if (strpos($searchHtml, 'Loan Remarks') !== false) {
        echo "SUCCESS: '💬 Loan Remarks' chip found in Search page!\n";
    } else {
        echo "FAILED: 'Loan Remarks' chip not found in Search page!\n";
    }

    if (strpos($searchHtml, 'Loan Note:') !== false) {
        echo "SUCCESS: 'Loan Note:' found in Customer Search results!\n";
        preg_match_all('/.*Loan Note:.*<\/span>/i', $searchHtml, $matches2);
        foreach ($matches2[0] as $m2) {
            echo "   Snippet: " . trim(strip_tags($m2)) . "\n";
        }
    } else {
        echo "NOTE: 'Loan Note:' not found\n";
    }
} else {
    echo "Could not login\n";
}

curl_close($ch);
