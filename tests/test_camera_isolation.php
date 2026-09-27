<?php
declare(strict_types=1);

echo "=== CAMERA ISOLATION & VERIFICATION TEST ===\n\n";

// 1. Check Dashboard
$dashHtml = @file_get_contents('http://localhost/TransactionManagement/');
if ($dashHtml !== false) {
    $hasCamScript = strpos($dashHtml, 'camera-capture.js') !== false;
    $hasCamModal  = strpos($dashHtml, 'global-camera-modal') !== false;
    $hasLiveProof = strpos($dashHtml, 'Live Camera Proof Capture') !== false;

    echo "[DASHBOARD] camera-capture.js present: " . ($hasCamScript ? "FAIL (Found)" : "PASS (None)") . "\n";
    echo "[DASHBOARD] global-camera-modal present: " . ($hasCamModal ? "FAIL (Found)" : "PASS (None)") . "\n";
    echo "[DASHBOARD] Live Camera Proof text: " . ($hasLiveProof ? "FAIL (Found)" : "PASS (None)") . "\n";
    assert(!$hasCamScript && !$hasCamModal && !$hasLiveProof, "Dashboard must NOT contain any camera elements");
} else {
    echo "[DASHBOARD] Could not fetch directly (local server check)\n";
}

// 2. Scan view files to verify camera is ONLY in collateral
$viewsDir = __DIR__ . '/../app/Views';
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir));

$camScriptFiles = [];
$openModalFiles = [];

foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getRealPath());
        $relPath = str_replace(realpath($viewsDir) . DIRECTORY_SEPARATOR, '', $file->getRealPath());
        
        if (strpos($content, 'camera-capture.js') !== false) {
            $camScriptFiles[] = $relPath;
        }
        if (strpos($content, 'openCameraModal') !== false) {
            $openModalFiles[] = $relPath;
        }
    }
}

echo "\n[VIEW AUDIT] Files containing camera-capture.js:\n";
foreach ($camScriptFiles as $f) {
    echo "  -> {$f}\n";
}

echo "\n[VIEW AUDIT] Files containing openCameraModal:\n";
foreach ($openModalFiles as $f) {
    echo "  -> {$f}\n";
}

// Assert camera is strictly in collateral and customer views
foreach ($camScriptFiles as $f) {
    $normalized = str_replace('\\', '/', $f);
    assert(strpos($normalized, 'collateral/') === 0 || strpos($normalized, 'customers/') === 0, "camera-capture.js should ONLY be in collateral and customer views, found in: {$f}");
}

foreach ($openModalFiles as $f) {
    $normalized = str_replace('\\', '/', $f);
    assert(strpos($normalized, 'collateral/') === 0 || strpos($normalized, 'customers/') === 0, "openCameraModal should ONLY be in collateral and customer views, found in: {$f}");
}

// 3. Verify camera-capture.js content
$jsContent = file_get_contents(__DIR__ . '/../public/assets/js/camera-capture.js');
$hasAutoMount = strpos($jsContent, 'DOMContentLoaded') !== false;
echo "\n[JS AUDIT] camera-capture.js has DOMContentLoaded: " . ($hasAutoMount ? "FAIL (Auto-mounts!)" : "PASS (No auto-mount)") . "\n";
assert(!$hasAutoMount, "camera-capture.js must NEVER auto-mount on DOMContentLoaded");

echo "\n=== ALL ISOLATION AUDITS PASSED CLEANLY! ===\n";
