<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\DashboardModel;
use App\Models\BackupModel;

class DashboardController extends BaseController {

    public function index(): void {
        AuthMiddleware::check();

        $kpis = DashboardModel::getKpiStats();
        $widgets = DashboardModel::getWidgetsData();

        $backupDue      = BackupModel::isBackupDue(7);
        $lastBackupDate = BackupModel::getLastBackupDate();

        $this->render('dashboard.index', array_merge([
            'pageTitle'      => 'Dashboard Overview',
            'kpis'           => $kpis,
            'backupDue'      => $backupDue,
            'lastBackupDate' => $lastBackupDate
        ], $widgets));
    }
}
