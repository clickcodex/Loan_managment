<?php

namespace App\Controllers;

use App\Helpers\Session;

abstract class BaseController {

    protected function render(string $view, array $data = []): void {
        Session::start();
        
        // Extract data variables for the view
        extract($data);
        
        // Make session & base URL available in views
        $baseUrl = defined('BASE_URL') ? BASE_URL : '';
        $csrfToken = Session::csrfToken();
        $flashSuccess = Session::getFlash('success');
        $flashError = Session::getFlash('error');
        $currentUser = Session::get('admin_user');

        $viewPath = __DIR__ . "/../Views/" . str_replace('.', '/', $view) . ".php";
        if (!file_exists($viewPath)) {
            die("View file not found: " . $viewPath);
        }

        require $viewPath;
    }

    protected function json(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function redirect(string $url): void {
        $baseUrl = defined('BASE_URL') ? BASE_URL : '';
        $target = (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) 
            ? $url 
            : $baseUrl . '/' . ltrim($url, '/');
            
        header("Location: " . $target);
        exit;
    }

    protected function validateCsrf(): void {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Session::validateCsrf($token)) {
            Session::setFlash('error', 'Invalid security token (CSRF). Please try again.');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/login');
        }
    }

    protected function handleFileUpload(array $file, string $folder = 'documents'): ?string {
        if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'doc', 'docx', 'mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', '3gp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts)) {
            return null;
        }

        $targetDir = __DIR__ . '/../../public/uploads/' . trim($folder, '/') . '/';
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = uniqid('doc_') . '_' . time() . '.' . $ext;
        $targetFile = $targetDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            return 'uploads/' . trim($folder, '/') . '/' . $fileName;
        }

        return null;
    }

    /**
     * Process base64 data URI captured from camera or standard file upload.
     */
    protected function handleBase64Upload(string $base64Data, string $folder = 'documents'): ?string {
        if (empty($base64Data)) {
            return null;
        }

        $type = 'jpg';
        if (preg_match('/^data:image\/([a-zA-Z0-9\+\-]+).*?;base64,/', $base64Data, $matches)) {
            $type = strtolower($matches[1]);
            if ($type === 'jpeg') $type = 'jpg';
            $data = substr($base64Data, strpos($base64Data, ',') + 1);
        } else {
            $data = $base64Data;
        }

        if (!in_array($type, ['jpg', 'png', 'webp', 'gif'])) {
            $type = 'jpg';
        }

        // Clean up possible URL-encoding artifacts where plus was replaced by space
        $data = str_replace(' ', '+', $data);
        $decodedData = base64_decode($data);
        if ($decodedData === false || strlen($decodedData) === 0) {
            return null;
        }

        $targetDir = __DIR__ . '/../../public/uploads/' . trim($folder, '/') . '/';
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = 'cam_' . uniqid() . '_' . time() . '.' . $type;
        $targetFile = $targetDir . $fileName;

        if (file_put_contents($targetFile, $decodedData) !== false) {
            return 'uploads/' . trim($folder, '/') . '/' . $fileName;
        }

        return null;
    }

    /**
     * Seamlessly handles either standard $_FILES upload OR camera base64 capture.
     */
    protected function handleBase64OrFileUpload(string $fileKey, string $base64Key = 'camera_photo_base64', string $folder = 'documents'): ?string {
        // 1. Check if direct camera base64 data is present
        if (!empty($_POST[$base64Key])) {
            $savedPath = $this->handleBase64Upload($_POST[$base64Key], $folder);
            if ($savedPath) {
                return $savedPath;
            }
        }

        // 2. Check if standard $_FILES was uploaded
        if (!empty($_FILES[$fileKey]['name']) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            return $this->handleFileUpload($_FILES[$fileKey], $folder);
        }

        return null;
    }
}
