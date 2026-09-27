<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\SearchModel;

class SearchController extends BaseController {

    public function index(): void {
        AuthMiddleware::check();

        $query = trim($_GET['q'] ?? $_GET['query'] ?? '');
        $results = !empty($query) ? SearchModel::performGlobalSearch($query) : [
            'query'       => '',
            'total_count' => 0,
            'customers'   => [],
            'loans'       => [],
            'items'       => [],
            'payments'    => []
        ];

        $this->render('search.index', [
            'pageTitle' => !empty($query) ? 'Global Search: "' . htmlspecialchars($query) . '"' : 'Global Search Engine',
            'query'     => $query,
            'results'   => $results
        ]);
    }
}
