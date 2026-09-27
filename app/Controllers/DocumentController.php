<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\DocumentModel;
use App\Models\Customer;
use App\Helpers\Session;
use App\Helpers\AuditLogger;

class DocumentController extends BaseController {

    public function index(): void {
        AuthMiddleware::check();

        $category   = trim($_GET['category'] ?? 'all');
        $customerId = intval($_GET['customer_id'] ?? 0);
        $search     = trim($_GET['search'] ?? '');

        $documents  = DocumentModel::getAllDocuments($category, $customerId, $search);
        $customers  = Customer::getAll('', 'Active');
        $stats      = DocumentModel::getVaultStats();

        $this->render('documents.index', [
            'pageTitle'  => 'Centralized Documents Vault',
            'documents'  => $documents,
            'customers'  => $customers,
            'stats'      => $stats,
            'category'   => $category,
            'customerId' => $customerId,
            'search'     => $search
        ]);
    }

    public function store(): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $customerId = intval($_POST['customer_id'] ?? 0);
        $docType    = trim($_POST['document_type'] ?? 'Other Document');

        if ($customerId <= 0) {
            Session::setFlash('error', 'Please select a valid Customer.');
            $this->redirect('/documents');
        }

        $filePath = $this->handleBase64OrFileUpload('document_file', 'camera_photo_base64', 'documents');

        if (!$filePath) {
            Session::setFlash('error', 'Please select a valid document file or capture proof with camera.');
            $this->redirect('/documents');
        }

        $origName = !empty($_FILES['document_file']['name'])
            ? $_FILES['document_file']['name']
            : ($docType . ' (Camera Vault Proof) - ' . date('d M Y H:i') . '.jpg');

        if ($filePath) {
            $docId = DocumentModel::addDocument($customerId, $docType, $filePath, $origName);

            AuditLogger::log('Vault Document Uploaded', Session::get('admin_id'), null, [
                'doc_id'      => $docId,
                'customer_id' => $customerId,
                'type'        => $docType,
                'file'        => $origName
            ], 'Uploaded new document to vault');

            Session::setFlash('success', 'Document / Camera Proof successfully uploaded to Vault!');
        } else {
            Session::setFlash('error', 'Failed to upload document file to server.');
        }

        $this->redirect('/documents');
    }

    public function download(string $id): void {
        AuthMiddleware::check();

        $d = DocumentModel::getDocumentById(intval($id));

        if (!$d || !$d['physical_exists']) {
            Session::setFlash('error', 'Document file not found on server.');
            $this->redirect('/documents');
        }

        $file = __DIR__ . '/../../public/' . ltrim($d['file_path'], '/');

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($d['original_name']) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }

    public function delete(string $id): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $intId = intval($id);
        $d = DocumentModel::getDocumentById($intId);

        if ($d) {
            DocumentModel::deleteDocument($intId);
            AuditLogger::log('Vault Document Deleted', Session::get('admin_id'), null, [
                'doc_id'      => $intId,
                'customer_id' => $d['customer_id'],
                'file'        => $d['original_name']
            ], 'Deleted document from vault');

            Session::setFlash('success', 'Document removed from Vault.');
        }

        $this->redirect('/documents');
    }
}
