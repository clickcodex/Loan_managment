<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\DueModel;
use App\Models\PaymentModel;

class DueController extends BaseController {

    public function index(): void {
        AuthMiddleware::check();

        $filter = trim($_GET['filter'] ?? 'all');
        $search = trim($_GET['search'] ?? '');

        $dueSheet = DueModel::getDueSheet($filter, $search);
        $kpis     = DueModel::getDueKpis();
        $nextReceiptNumber = PaymentModel::generateNextReceiptNumber();

        $this->render('dues.index', [
            'pageTitle'         => 'Due Sheet & Overdue Alerts Engine',
            'dueSheet'          => $dueSheet,
            'kpis'              => $kpis,
            'filter'            => $filter,
            'search'            => $search,
            'nextReceiptNumber' => $nextReceiptNumber
        ]);
    }

    public function printSheet(): void {
        AuthMiddleware::check();

        $filter = trim($_GET['filter'] ?? 'all');
        $search = trim($_GET['search'] ?? '');

        $dueSheet = DueModel::getDueSheet($filter, $search);

        $this->render('dues.print', [
            'pageTitle' => 'Printable Due Sheet - Collection Report',
            'dueSheet'  => $dueSheet,
            'filter'    => $filter
        ]);
    }
}
