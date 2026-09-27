<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\CollateralModel;
use App\Models\Loan;
use App\Models\RateModel;
use App\Helpers\Session;
use App\Helpers\AuditLogger;
use App\Helpers\RackManager;

class CollateralController extends BaseController {

    public function index(): void {
        AuthMiddleware::check();

        $search       = trim($_GET['search'] ?? '');
        $itemType     = trim($_GET['type'] ?? '');
        $purityPreset = trim($_GET['purity'] ?? '');

        $items = CollateralModel::getAll($search, $itemType, $purityPreset);
        $stats = CollateralModel::getPortfolioStats();
        $unassignedCount = RackManager::getUnassignedCount();

        $this->render('collateral.index', [
            'pageTitle'       => 'Collateral Vault & Inventory Directory',
            'items'           => $items,
            'stats'           => $stats,
            'unassignedCount' => $unassignedCount,
            'search'          => $search,
            'itemType'        => $itemType,
            'purityPreset'    => $purityPreset
        ]);
    }

    public function racks(): void {
        AuthMiddleware::check();

        $gridData = RackManager::getRackGridData();
        $unassignedCount = RackManager::getUnassignedCount();

        $this->render('collateral.racks', [
            'pageTitle'       => 'Physical 10-Rack Storage Visualizer',
            'gridData'        => $gridData,
            'unassignedCount' => $unassignedCount
        ]);
    }

    public function autoAssign(): void {
        AuthMiddleware::check();

        $assignedCount = RackManager::autoAssignAllUnassigned();

        if ($assignedCount > 0) {
            AuditLogger::log('Rack Auto-Assigned', Session::get('admin_id'), null, [
                'count' => $assignedCount
            ], 'Auto-assigned rack slots to unassigned collateral items');

            Session::setFlash('success', sprintf('Successfully auto-assigned rack slots to %d collateral item(s)!', $assignedCount));
        } else {
            Session::setFlash('info', 'All active collateral items already have assigned rack slots.');
        }

        $redirectUrl = $_SERVER['HTTP_REFERER'] ?? '/collateral/racks';
        $this->redirect($redirectUrl);
    }

    public function show(string $id): void {
        AuthMiddleware::check();

        $itemIntId = intval($id);
        $item = CollateralModel::findById($itemIntId);

        if (!$item) {
            Session::setFlash('error', 'Collateral item not found.');
            $this->redirect('/collateral');
        }

        $photos = CollateralModel::getPhotos($itemIntId);
        $goldRate = RateModel::getLatestGoldRate();
        $silverRate = RateModel::getLatestSilverRate();

        $this->render('collateral.show', [
            'pageTitle'  => 'Collateral Details - ' . htmlspecialchars($item['item_name']),
            'item'       => $item,
            'photos'     => $photos,
            'goldRate'   => $goldRate,
            'silverRate' => $silverRate
        ]);
    }

    public function create(string $loanId): void {
        AuthMiddleware::check();

        $loanIntId = intval($loanId);
        $loan = Loan::findById($loanIntId);

        if (!$loan) {
            Session::setFlash('error', 'Loan account not found.');
            $this->redirect('/loans');
        }

        $goldRate      = RateModel::getLatestGoldRate();
        $silverRate    = RateModel::getLatestSilverRate();
        $goldPresets   = RateModel::getKaratPresets('GOLD');
        $silverPresets = RateModel::getKaratPresets('SILVER');

        $this->render('collateral.create', [
            'pageTitle'     => 'Add Collateral Item - ' . htmlspecialchars($loan['loan_number']),
            'loan'          => $loan,
            'goldRate'      => $goldRate,
            'silverRate'    => $silverRate,
            'goldPresets'   => $goldPresets,
            'silverPresets' => $silverPresets
        ]);
    }

    public function store(): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $loanId    = intval($_POST['loan_id'] ?? 0);
        $itemName  = trim($_POST['item_name'] ?? '');
        $itemType  = strtoupper(trim($_POST['item_type'] ?? 'GOLD'));
        $grossWt   = floatval($_POST['gross_weight'] ?? 0);
        $stoneWt   = floatval($_POST['stone_weight'] ?? 0);
        $netWt     = max(0, $grossWt - $stoneWt);

        if ($loanId <= 0 || empty($itemName) || $grossWt <= 0) {
            Session::setFlash('error', 'Item name and gross weight are required.');
            $this->redirect('/loans/' . $loanId);
        }

        $purityPreset = $_POST['purity_preset'] ?? ($itemType === 'GOLD' ? '24K' : '100% (Pure)');
        $purityPct    = floatval($_POST['purity_percentage'] ?? 0);

        if ($purityPct <= 0) {
            if ($purityPreset === '24K') $purityPct = 100.00;
            elseif ($purityPreset === '22K') $purityPct = 91.67;
            elseif ($purityPreset === '20K') $purityPct = 83.33;
            elseif ($purityPreset === '18K') $purityPct = 75.00;
            elseif ($purityPreset === '14K') $purityPct = 58.33;
            elseif ($purityPreset === '10K') $purityPct = 41.67;
            elseif ($purityPreset === '99.9%') $purityPct = 99.90;
            elseif ($purityPreset === '92.5%') $purityPct = 92.50;
            elseif ($purityPreset === '80.0%') $purityPct = 80.00;
            else $purityPct = 100.00;
        }

        // Auto calculate market value based on 24K Gold or 999 Silver base rate
        $goldRate   = RateModel::getLatestGoldRate();
        $silverRate = RateModel::getLatestSilverRate();

        if ($itemType === 'GOLD') {
            $base100 = floatval($goldRate['rate_100'] ?? round(floatval($goldRate['rate_24k'] ?? 79920) / 0.999, 2));
            $marketValue = $netWt * ($purityPct / 100.0) * ($base100 / 10.0);
        } else {
            $base100 = floatval($silverRate['rate_100'] ?? round(floatval($silverRate['rate_999'] ?? 90) / 0.999, 2));
            $marketValue = $netWt * ($purityPct / 100.0) * $base100;
        }

        $overrideVal = !empty($_POST['manual_market_value_override']) ? floatval($_POST['manual_market_value_override']) : null;

        $itemData = [
            'loan_id'                      => $loanId,
            'item_type'                    => $itemType,
            'item_name'                    => $itemName,
            'quantity'                     => intval($_POST['quantity'] ?? 1),
            'gross_weight'                 => $grossWt,
            'stone_weight'                 => $stoneWt,
            'net_weight'                   => $netWt,
            'purity_preset'                => $purityPreset,
            'purity_percentage'            => $purityPct,
            'market_value'                 => $marketValue,
            'manual_market_value_override' => $overrideVal,
            'loan_value'                   => floatval($_POST['loan_value'] ?? 0),
            'rk_number'                    => trim($_POST['rk_number'] ?? ''),
            'remarks'                      => trim($_POST['remarks'] ?? '')
        ];

        $newItemId = CollateralModel::createItem($itemData);

        // Handle Photo Upload (File or Live Camera)
        $photoPath = $this->handleBase64OrFileUpload('item_photo', 'camera_photo_base64', 'items');
        if (!$photoPath && !empty($_FILES['photo_file']['name']) && $_FILES['photo_file']['error'] === UPLOAD_ERR_OK) {
            $photoPath = $this->handleFileUpload($_FILES['photo_file'], 'items');
        }

        if ($photoPath) {
            $origName = !empty($_FILES['item_photo']['name']) 
                ? $_FILES['item_photo']['name'] 
                : (!empty($_FILES['photo_file']['name']) ? $_FILES['photo_file']['name'] : ($itemName . ' Photo.jpg'));
            CollateralModel::addPhoto($newItemId, $photoPath, $origName);
        }

        AuditLogger::log('Collateral Item Added', Session::get('admin_id'), null, [
            'item_id'   => $newItemId,
            'loan_id'   => $loanId,
            'item_name' => $itemName,
            'item_type' => $itemType,
            'net_wt'    => $netWt
        ], 'Added new pledged collateral item to loan');

        Session::setFlash('success', 'Collateral item added successfully.');
        $this->redirect('/collateral/' . $newItemId);
    }

    public function edit(string $id): void {
        AuthMiddleware::check();

        $itemIntId = intval($id);
        $item = CollateralModel::findById($itemIntId);

        if (!$item) {
            Session::setFlash('error', 'Collateral item not found.');
            $this->redirect('/collateral');
        }

        $goldRate      = RateModel::getLatestGoldRate();
        $silverRate    = RateModel::getLatestSilverRate();
        $goldPresets   = RateModel::getKaratPresets('GOLD');
        $silverPresets = RateModel::getKaratPresets('SILVER');

        $this->render('collateral.edit', [
            'pageTitle'     => 'Edit Collateral - ' . htmlspecialchars($item['item_name']),
            'item'          => $item,
            'goldRate'      => $goldRate,
            'silverRate'    => $silverRate,
            'goldPresets'   => $goldPresets,
            'silverPresets' => $silverPresets
        ]);
    }

    public function update(string $id): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $itemIntId = intval($id);
        $oldItem = CollateralModel::findById($itemIntId);

        if (!$oldItem) {
            Session::setFlash('error', 'Collateral item not found.');
            $this->redirect('/collateral');
        }

        $itemName = trim($_POST['item_name'] ?? '');
        $grossWt  = floatval($_POST['gross_weight'] ?? 0);
        $stoneWt  = floatval($_POST['stone_weight'] ?? 0);
        $netWt    = max(0, $grossWt - $stoneWt);

        if (empty($itemName) || $grossWt <= 0) {
            Session::setFlash('error', 'Item name and gross weight are required.');
            $this->redirect('/collateral/' . $itemIntId . '/edit');
        }

        $purityPreset = $_POST['purity_preset'] ?? $oldItem['purity_preset'];
        $purityPct    = floatval($_POST['purity_percentage'] ?? 0);

        if ($purityPct <= 0) {
            if ($purityPreset === '24K') $purityPct = 100.00;
            elseif ($purityPreset === '22K') $purityPct = 91.67;
            elseif ($purityPreset === '20K') $purityPct = 83.33;
            elseif ($purityPreset === '18K') $purityPct = 75.00;
            elseif ($purityPreset === '14K') $purityPct = 58.33;
            elseif ($purityPreset === '10K') $purityPct = 41.67;
            elseif ($purityPreset === '99.9%') $purityPct = 99.90;
            elseif ($purityPreset === '92.5%') $purityPct = 92.50;
            elseif ($purityPreset === '80.0%') $purityPct = 80.00;
            else $purityPct = floatval($oldItem['purity_percentage']);
        }

        $goldRate   = RateModel::getLatestGoldRate();
        $silverRate = RateModel::getLatestSilverRate();

        if ($oldItem['item_type'] === 'GOLD') {
            $base100 = floatval($goldRate['rate_100'] ?? round(floatval($goldRate['rate_24k'] ?? 79920) / 0.999, 2));
            $marketValue = $netWt * ($purityPct / 100.0) * ($base100 / 10.0);
        } else {
            $base100 = floatval($silverRate['rate_100'] ?? round(floatval($silverRate['rate_999'] ?? 90) / 0.999, 2));
            $marketValue = $netWt * ($purityPct / 100.0) * $base100;
        }

        $itemData = [
            'item_name'                    => $itemName,
            'quantity'                     => intval($_POST['quantity'] ?? 1),
            'gross_weight'                 => $grossWt,
            'stone_weight'                 => $stoneWt,
            'net_weight'                   => $netWt,
            'purity_preset'                => $purityPreset,
            'purity_percentage'            => $purityPct,
            'market_value'                 => $marketValue,
            'manual_market_value_override' => !empty($_POST['manual_market_value_override']) ? floatval($_POST['manual_market_value_override']) : null,
            'loan_value'                   => floatval($_POST['loan_value'] ?? 0),
            'rk_number'                    => trim($_POST['rk_number'] ?? ''),
            'remarks'                      => trim($_POST['remarks'] ?? '')
        ];

        CollateralModel::updateItem($itemIntId, $itemData);

        AuditLogger::log('Collateral Item Updated', Session::get('admin_id'), $oldItem, $itemData, 'Updated pledged collateral item');

        Session::setFlash('success', 'Collateral item updated successfully.');
        $this->redirect('/collateral/' . $itemIntId);
    }

    public function uploadPhoto(string $id): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $itemIntId = intval($id);

        $photoPath = $this->handleBase64OrFileUpload('photo_file', 'camera_photo_base64', 'items');
        if (!$photoPath && !empty($_FILES['item_photo']['name']) && $_FILES['item_photo']['error'] === UPLOAD_ERR_OK) {
            $photoPath = $this->handleFileUpload($_FILES['item_photo'], 'items');
        }

        if ($photoPath) {
            $origName = !empty($_FILES['photo_file']['name']) 
                ? $_FILES['photo_file']['name'] 
                : (!empty($_FILES['item_photo']['name']) ? $_FILES['item_photo']['name'] : ('Collateral Photo - ' . date('Y-m-d H:i') . '.jpg'));

            CollateralModel::addPhoto($itemIntId, $photoPath, $origName);
            AuditLogger::log('Collateral Photo Uploaded', Session::get('admin_id'), null, ['item_id' => $itemIntId], 'Uploaded item photo');
            Session::setFlash('success', 'Collateral item photo uploaded successfully.');
        } else {
            Session::setFlash('error', 'Please select a valid image file or take a photo with camera.');
        }

        $this->redirect('/collateral/' . $itemIntId);
    }

    public function deletePhoto(string $photoId): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $intPhotoId = intval($photoId);
        $photo = CollateralModel::findPhotoById($intPhotoId);

        if ($photo) {
            $itemId = $photo['item_id'];
            CollateralModel::deletePhoto($intPhotoId);

            $fullFilePath = __DIR__ . '/../../public/' . ltrim($photo['file_path'], '/');
            if (file_exists($fullFilePath)) {
                @unlink($fullFilePath);
            }

            Session::setFlash('success', 'Photo removed.');
            $this->redirect('/collateral/' . $itemId);
        } else {
            Session::setFlash('error', 'Photo not found.');
            $this->redirect('/collateral');
        }
    }
}
