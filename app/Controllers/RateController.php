<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\RateModel;
use App\Models\CollateralModel;
use App\Helpers\Session;
use App\Helpers\AuditLogger;

class RateController extends BaseController {

    public function index(): void {
        AuthMiddleware::check();

        $goldRate    = RateModel::getLatestGoldRate();
        $silverRate  = RateModel::getLatestSilverRate();
        $goldHistory = RateModel::getGoldHistory(10);
        $silverHistory = RateModel::getSilverHistory(10);

        $goldPresets   = RateModel::getKaratPresets('GOLD');
        $silverPresets = RateModel::getKaratPresets('SILVER');

        $this->render('rates.index', [
            'pageTitle'     => 'Multi-Karat Gold & Silver Rates Management',
            'goldRate'      => $goldRate,
            'silverRate'    => $silverRate,
            'goldHistory'   => $goldHistory,
            'silverHistory' => $silverHistory,
            'goldPresets'   => $goldPresets,
            'silverPresets' => $silverPresets
        ]);
    }

    public function updateGold(): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $rate100 = !empty($_POST['rate_100']) ? floatval($_POST['rate_100']) : 0;

        if ($rate100 <= 0) {
            Session::setFlash('error', 'Please enter a valid 100% Pure Gold Base Rate (₹ / 10g).');
            $this->redirect('/rates');
        }

        $rate24k = round($rate100 * 0.999, 2);
        $rate22k = round($rate100 * 0.9167, 2);
        $rate18k = round($rate100 * 0.75, 2);
        $rate14k = round($rate100 * 0.5833, 2);

        $data = [
            'rate_100'     => $rate100,
            'rate_24k'     => $rate24k,
            'rate_22k'     => $rate22k,
            'rate_18k'     => $rate18k,
            'rate_14k'     => $rate14k,
            'custom_rates' => null,
            'rate_date'    => $_POST['rate_date'] ?? date('Y-m-d'),
            'remarks'      => trim($_POST['remarks'] ?? ''),
            'created_by'   => Session::get('admin_id')
        ];

        RateModel::updateGoldRates($data);
        $updatedCount = CollateralModel::recalculateCollateralMarketValues('GOLD', $rate100);

        AuditLogger::log('Gold Rates Updated', Session::get('admin_id'), null, array_merge($data, [
            'collaterals_updated' => $updatedCount
        ]), 'Updated daily 100% pure gold rate & recalculated collateral values');

        Session::setFlash('success', '100% Pure Gold Rate updated successfully (₹' . number_format($rate100, 2) . ' / 10g)! ' . $updatedCount . ' gold collateral item(s) re-valued based on purity.');
        $this->redirect('/rates');
    }

    public function updateSilver(): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $rate100 = !empty($_POST['rate_100']) ? floatval($_POST['rate_100']) : 0;

        if ($rate100 <= 0) {
            Session::setFlash('error', 'Please enter a valid 100% Pure Silver Base Rate (₹ / gram).');
            $this->redirect('/rates');
        }

        $rate999 = round($rate100 * 0.999, 2);
        $rate925 = round($rate100 * 0.925, 2);
        $rate800 = round($rate100 * 0.800, 2);

        $data = [
            'rate_100'     => $rate100,
            'rate_999'     => $rate999,
            'rate_925'     => $rate925,
            'rate_800'     => $rate800,
            'custom_rates' => null,
            'rate_date'    => $_POST['rate_date'] ?? date('Y-m-d'),
            'remarks'      => trim($_POST['remarks'] ?? ''),
            'created_by'   => Session::get('admin_id')
        ];

        RateModel::updateSilverRates($data);
        $updatedCount = CollateralModel::recalculateCollateralMarketValues('SILVER', $rate100);

        AuditLogger::log('Silver Rates Updated', Session::get('admin_id'), null, array_merge($data, [
            'collaterals_updated' => $updatedCount
        ]), 'Updated daily 100% pure silver rate & recalculated collateral values');

        Session::setFlash('success', '100% Pure Silver Rate updated successfully (₹' . number_format($rate100, 2) . ' / g)! ' . $updatedCount . ' silver collateral item(s) re-valued based on purity.');
        $this->redirect('/rates');
    }

    public function syncCollaterals(): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $goldUpdated   = CollateralModel::recalculateCollateralMarketValues('GOLD');
        $silverUpdated = CollateralModel::recalculateCollateralMarketValues('SILVER');
        $totalUpdated  = $goldUpdated + $silverUpdated;

        AuditLogger::log('Collaterals Re-valued', Session::get('admin_id'), null, [
            'gold_updated'   => $goldUpdated,
            'silver_updated' => $silverUpdated,
            'total_updated'  => $totalUpdated
        ], 'Manually triggered full collateral valuation synchronization against latest base rates & purities');

        Session::setFlash('success', 'Collateral revaluation synchronized successfully! ' . $totalUpdated . ' item(s) updated (' . $goldUpdated . ' Gold, ' . $silverUpdated . ' Silver) based on purity.');
        $this->redirect('/rates');
    }

    public function addPreset(): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $type      = strtoupper(trim($_POST['type'] ?? 'GOLD'));
        $name      = trim($_POST['name'] ?? '');
        $purityPct = floatval($_POST['purity_percentage'] ?? 0);

        if (empty($name) || $purityPct <= 0) {
            Session::setFlash('error', 'Preset name and valid purity percentage are required.');
            $this->redirect('/rates');
        }

        RateModel::addKaratPreset($type, $name, $purityPct);

        Session::setFlash('success', 'New Karat preset added: ' . htmlspecialchars($name));
        $this->redirect('/rates');
    }

    public function deletePreset(string $id): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $presetId = intval($id);
        RateModel::deleteKaratPreset($presetId);

        Session::setFlash('success', 'Karat preset deleted.');
        $this->redirect('/rates');
    }
}
