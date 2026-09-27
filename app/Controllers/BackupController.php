<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\BackupModel;
use App\Helpers\Session;
use App\Helpers\AuditLogger;

class BackupController extends BaseController {

    public function index(): void {
        AuthMiddleware::check();

        $backups     = BackupModel::getAllBackups();
        $lastBackup  = BackupModel::getLastBackupDate();
        $isDue       = BackupModel::isBackupDue(7);

        $this->render('backups.index', [
            'pageTitle'  => 'Database Backup & Restore System',
            'backups'    => $backups,
            'lastBackup' => $lastBackup,
            'isDue'      => $isDue
        ]);
    }

    public function create(): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $result = BackupModel::generateBackup();

        if ($result) {
            AuditLogger::log('Backup Generated', Session::get('admin_id'), null, $result, 'Generated manual database backup');
            Session::setFlash('success', 'Database backup successfully created: ' . htmlspecialchars($result['filename']));
        } else {
            Session::setFlash('error', 'Failed to generate database backup dump.');
        }

        $this->redirect('/backups');
    }

    public function eraseAndBackup(): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        // Step 1: Generate safety backup first
        $backup = BackupModel::generateBackup();

        if (!$backup) {
            Session::setFlash('error', 'Safety backup failed! Aborting database erase operation to protect your data.');
            $this->redirect('/backups');
        }

        // Step 2: Erase operational database tables
        $erased = BackupModel::eraseAndResetDatabase();

        if ($erased) {
            AuditLogger::log('System Erased & Reset', Session::get('admin_id'), null, ['backup_file' => $backup['filename']], 'Erased all operational data with automatic safety backup download');

            // Step 3: Trigger automatic download of the safety backup file!
            $this->download($backup['id']);
        } else {
            Session::setFlash('error', 'Failed to erase database tables.');
            $this->redirect('/backups');
        }
    }

    public function download(string $id): void {
        AuthMiddleware::check();

        $b = BackupModel::findBackupById(intval($id));

        if (!$b || !$b['physical_exists']) {
            Session::setFlash('error', 'Backup file not found or has been deleted.');
            $this->redirect('/backups');
        }

        $file = $b['filepath'];

        header('Content-Description: File Transfer');
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }

    public function restore(string $id): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $b = BackupModel::findBackupById(intval($id));

        if (!$b || !$b['physical_exists']) {
            Session::setFlash('error', 'Backup file not found.');
            $this->redirect('/backups');
        }

        $success = BackupModel::restoreFromSqlFile($b['filepath']);

        if ($success) {
            AuditLogger::log('Database Restored', Session::get('admin_id'), null, ['filename' => $b['filename']], 'Restored database from backup file');
            Session::setFlash('success', 'Database successfully restored from backup: ' . htmlspecialchars($b['filename']));
        } else {
            Session::setFlash('error', 'Failed to restore database from backup file.');
        }

        $this->redirect('/backups');
    }

    public function uploadAndRestore(): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        if (empty($_FILES['sql_file']['name']) || $_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) {
            Session::setFlash('error', 'Please select a valid .sql backup file to upload.');
            $this->redirect('/backups');
        }

        $ext = strtolower(pathinfo($_FILES['sql_file']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'sql') {
            Session::setFlash('error', 'Invalid file format. Only .sql backup files are allowed.');
            $this->redirect('/backups');
        }

        $tempPath = $_FILES['sql_file']['tmp_name'];
        $success = BackupModel::restoreFromSqlFile($tempPath);

        if ($success) {
            AuditLogger::log('Database Restored', Session::get('admin_id'), null, ['uploaded_file' => $_FILES['sql_file']['name']], 'Restored database from uploaded .sql file');
            Session::setFlash('success', 'Database successfully restored from uploaded SQL file!');
        } else {
            Session::setFlash('error', 'Failed to execute SQL restoration from uploaded file.');
        }

        $this->redirect('/backups');
    }

    public function delete(string $id): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        $intId = intval($id);
        $b = BackupModel::findBackupById($intId);

        if ($b) {
            BackupModel::deleteBackup($intId);
            AuditLogger::log('Backup Deleted', Session::get('admin_id'), null, ['filename' => $b['filename']], 'Deleted backup file');
            Session::setFlash('success', 'Backup file deleted.');
        }

        $this->redirect('/backups');
    }
}
