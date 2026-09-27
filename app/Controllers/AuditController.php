<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\AuditModel;

class AuditController extends BaseController {

    public function index(): void {
        AuthMiddleware::check();

        $category = trim($_GET['category'] ?? 'all');
        $search   = trim($_GET['search'] ?? '');
        $page     = max(1, intval($_GET['page'] ?? 1));
        $limit    = 30;
        $offset   = ($page - 1) * $limit;

        $logs       = AuditModel::getLogs($category, $search, $limit, $offset);
        $totalLogs  = AuditModel::getTotalLogsCount($category, $search);
        $totalPages = ceil($totalLogs / $limit);
        $kpis       = AuditModel::getAuditKpis();

        $this->render('audit.index', [
            'pageTitle'  => 'Audit Logs & Security Trail',
            'logs'       => $logs,
            'totalLogs'  => $totalLogs,
            'page'       => $page,
            'totalPages' => $totalPages,
            'category'   => $category,
            'search'     => $search,
            'kpis'       => $kpis
        ]);
    }

    public function show(string $id): void {
        AuthMiddleware::check();

        $log = AuditModel::getLogById(intval($id));

        header('Content-Type: application/json');
        if ($log) {
            echo json_encode(['success' => true, 'log' => $log]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Audit log record not found.']);
        }
        exit;
    }
}
