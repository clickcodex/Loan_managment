<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\ReportModel;

class ReportController extends BaseController {

    public function index(): void {
        AuthMiddleware::check();

        $reportsMeta = ReportModel::getReportMeta();

        $this->render('reports.index', [
            'pageTitle'   => '17 System Reports Directory Hub',
            'reportsMeta' => $reportsMeta
        ]);
    }

    public function show(string $key): void {
        AuthMiddleware::check();

        $startDate = trim($_GET['start_date'] ?? '');
        $endDate   = trim($_GET['end_date'] ?? '');
        $search    = trim($_GET['search'] ?? '');

        $reportData = ReportModel::generateReport($key, $startDate ?: null, $endDate ?: null, $search);

        $this->render('reports.show', [
            'pageTitle' => $reportData['meta']['name'],
            'report'    => $reportData
        ]);
    }

    public function exportCsv(string $key): void {
        AuthMiddleware::check();

        $startDate = trim($_GET['start_date'] ?? '');
        $endDate   = trim($_GET['end_date'] ?? '');
        $search    = trim($_GET['search'] ?? '');

        $reportData = ReportModel::generateReport($key, $startDate ?: null, $endDate ?: null, $search);

        $filename = 'report_' . $key . '_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // Write Title Header
        fputcsv($output, [$reportData['meta']['name']]);
        fputcsv($output, ['Generated Date: ' . date('Y-m-d H:i:s')]);
        fputcsv($output, []);

        // Write Column Headers
        fputcsv($output, $reportData['columns']);

        // Write Data Rows
        foreach ($reportData['rows'] as $row) {
            fputcsv($output, $row);
        }

        // Write Totals Summary
        if (!empty($reportData['totals'])) {
            fputcsv($output, []);
            fputcsv($output, ['--- REPORT TOTALS SUMMARY ---']);
            foreach ($reportData['totals'] as $label => $val) {
                fputcsv($output, [$label, $val]);
            }
        }

        fclose($output);
        exit;
    }

    public function printReport(string $key): void {
        AuthMiddleware::check();

        $startDate = trim($_GET['start_date'] ?? '');
        $endDate   = trim($_GET['end_date'] ?? '');
        $search    = trim($_GET['search'] ?? '');

        $reportData = ReportModel::generateReport($key, $startDate ?: null, $endDate ?: null, $search);

        $this->render('reports.print', [
            'pageTitle' => 'Printable - ' . $reportData['meta']['name'],
            'report'    => $reportData
        ]);
    }
}
