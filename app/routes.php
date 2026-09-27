<?php

require_once __DIR__ . '/Router.php';

$router = new Router();

// =============================================================================
// PUBLIC ROUTES (No Authentication Required)
// =============================================================================
$router->get('/',                 'DashboardController@index');
$router->get('login',             'AuthController@showLogin');
$router->post('login',            'AuthController@login');
$router->post('logout',           'AuthController@logout');

// =============================================================================
// PROTECTED ROUTES (Authentication Required)
// =============================================================================
$router->group(fn() => \App\Middleware\AuthMiddleware::check(), function() use ($router) {
    // Dashboard & Password
    $router->get('dashboard',                        'DashboardController@index');
    $router->get('home',                             'DashboardController@index');
    $router->get('change-password',                  'AuthController@showChangePassword');
    $router->post('change-password',                 'AuthController@changePassword');

    // Module 3: Customer Management Routes
    $router->get('customers',                        'CustomerController@index');
    $router->get('customers/create',                 'CustomerController@create');
    $router->post('customers/store',                 'CustomerController@store');
    $router->get('customers/{id}',                   'CustomerController@show');
    $router->get('customers/{id}/edit',              'CustomerController@edit');
    $router->post('customers/{id}/update',           'CustomerController@update');
    $router->post('customers/{id}/status',           'CustomerController@updateStatus');
    $router->post('customers/{id}/documents',        'CustomerController@uploadDocument');
    $router->post('customers/documents/{docId}/delete', 'CustomerController@deleteDocument');

    // Module 4: Unified Loan Management Routes
    $router->get('loans',                            'LoanController@index');
    $router->get('loans/create',                     'LoanController@create');
    $router->post('loans/store',                     'LoanController@store');
    $router->get('loans/{id}',                       'LoanController@show');
    $router->get('loans/{id}/edit',                  'LoanController@edit');
    $router->post('loans/{id}/update',               'LoanController@update');
    $router->post('loans/{id}/delete',               'LoanController@delete');
    $router->post('loans/{id}/upload-video',          'LoanController@uploadVideo');
    $router->get('loans/{id}/topup',                  'LoanController@topupForm');
    $router->post('loans/{id}/topup',                 'LoanController@topupStore');
    $router->post('loans/{id}/deliver-jewellery',     'LoanController@deliverJewellery');
    $router->post('loans/{loanId}/ledger/{id}/update', 'LoanController@updateLedgerEntry');
    $router->get('loan-ledger',                      'LoanController@allLedger');


    // Module 5 & 6: Collateral Vault & Dynamic Rack Management Routes
    $router->get('collateral',                       'CollateralController@index');
    $router->get('collateral/racks',                 'CollateralController@racks');
    $router->get('collateral/auto-assign',           'CollateralController@autoAssign');
    $router->post('collateral/auto-assign',          'CollateralController@autoAssign');
    $router->get('collateral/{id}',                  'CollateralController@show');
    $router->get('loans/{loanId}/collateral/create', 'CollateralController@create');
    $router->post('collateral/store',                'CollateralController@store');
    $router->get('collateral/{id}/edit',             'CollateralController@edit');
    $router->post('collateral/{id}/update',          'CollateralController@update');
    $router->post('collateral/{id}/photos',          'CollateralController@uploadPhoto');
    $router->post('collateral/photos/{photoId}/delete', 'CollateralController@deletePhoto');

    // Dynamic Rack & Slot Management Routes
    $router->post('racks/create',                    'RackController@createRack');
    $router->post('racks/{id}/update',               'RackController@updateRack');
    $router->post('racks/{id}/delete',               'RackController@deleteRack');
    $router->post('racks/{id}/slots/create',         'RackController@createSlot');
    $router->post('racks/slots/{id}/update',         'RackController@updateSlot');
    $router->post('racks/slots/{id}/delete',         'RackController@deleteSlot');
    $router->post('racks/slots/transfer',            'RackController@transferSlot');

    // Module 10 & 13: Payments & Receipt Printing Routes
    $router->get('payments',                         'PaymentController@index');
    $router->get('payments/create',                  'PaymentController@create');
    $router->post('payments/store',                  'PaymentController@store');
    $router->post('payments/{id}/update',            'PaymentController@update');
    $router->get('receipts/payment/{id}',            'PaymentController@showReceipt');
    $router->get('receipts/sanction/{id}',           'LoanController@showSanctionReceipt');
    $router->get('receipts/disbursement/{id}',       'LoanController@showDisbursementReceipt');
    $router->get('receipts/closure/{id}',            'LoanController@showClosureReceipt');

    // Module 14: Billing & Invoicing System Routes (Single-Table Multi-Product)
    $router->get('bills',                            'BillingController@index');
    $router->get('bills/create',                     'BillingController@create');
    $router->post('bills/store',                     'BillingController@store');
    $router->get('bills/{id}',                       'BillingController@show');
    $router->post('bills/{id}/delete',               'BillingController@delete');

    // Credit & Debit Cashbook (Daybook) Module Routes
    $router->get('transactions',                     'TransactionController@index');
    $router->get('transactions/export-pdf',          'TransactionController@exportPdf');

    // Module 8: Gold & Silver Daily Rates Routes
    $router->get('rates',                            'RateController@index');
    $router->post('rates/gold',                      'RateController@updateGold');
    $router->post('rates/silver',                    'RateController@updateSilver');
    $router->post('rates/sync-collaterals',          'RateController@syncCollaterals');
    $router->post('rates/presets/add',               'RateController@addPreset');
    $router->post('rates/presets/{id}/delete',        'RateController@deletePreset');

    // Module 9: Due Sheet & Overdue Alerts Routes
    $router->get('dues',                             'DueController@index');
    $router->get('dues/print',                       'DueController@printSheet');

    // Module 11: 17 System Reports Engine Routes
    $router->get('reports',                          'ReportController@index');
    $router->get('reports/{key}',                    'ReportController@show');
    $router->get('reports/{key}/export',             'ReportController@exportCsv');
    $router->get('reports/{key}/print',              'ReportController@printReport');

    // Module 15: Global Search Engine Routes
    $router->get('search',                           'SearchController@index');

    // Module 16: Backup & Restore Management Routes
    $router->get('backups',                          'BackupController@index');
    $router->post('backups/create',                  'BackupController@create');
    $router->post('backups/erase',                   'BackupController@eraseAndBackup');
    $router->get('backups/{id}/download',            'BackupController@download');
    $router->post('backups/{id}/restore',            'BackupController@restore');
    $router->post('backups/upload-restore',          'BackupController@uploadAndRestore');
    $router->post('backups/{id}/delete',             'BackupController@delete');

    // Audit Trail Routes
    $router->get('audit-logs',                       'AuditController@index');
    $router->get('audit-logs/{id}',                  'AuditController@show');

    // Document Vault Routes
    $router->get('documents',                        'DocumentController@index');
    $router->post('documents/store',                 'DocumentController@store');
    $router->get('documents/{id}/download',          'DocumentController@download');
    $router->post('documents/{id}/delete',           'DocumentController@delete');
});

// =============================================================================
// RESOLVE ROUTE
// =============================================================================
$router->resolve();
