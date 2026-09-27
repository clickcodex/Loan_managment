<?php

namespace App\Controllers;

use App\Helpers\Session;
use App\Helpers\AuditLogger;
use App\Models\Admin;

class AuthController extends BaseController {

    public function showLogin(): void {
        Session::start();
        if (Session::has('admin_id')) {
            $this->redirect('/dashboard');
        }
        $this->render('auth.login');
    }

    public function login(): void {
        $this->validateCsrf();

        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $remember = isset($_POST['remember']);

        if (empty($username) || empty($password)) {
            Session::setFlash('error', 'Please enter both username and password.');
            $this->redirect('/login');
        }

        $admin = Admin::findByUsername($username);

        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            AuditLogger::log('Admin Login Failed', null, null, ['username' => $username], 'Invalid credentials attempt');
            Session::setFlash('error', 'Invalid username or password.');
            $this->redirect('/login');
        }

        // Prevent Session Fixation
        session_regenerate_id(true);

        Session::set('admin_id', $admin['id']);
        Session::set('admin_user', [
            'id'       => $admin['id'],
            'username' => $admin['username'],
            'name'     => $admin['name'],
            'email'    => $admin['email']
        ]);

        $sessionId = session_id();
        Admin::updateLastLogin($admin['id'], $sessionId);

        // Handle Remember Me Cookie
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            Admin::updateRememberToken($admin['id'], $token);
            setcookie('remember_me', $token, time() + (86400 * 30), '/', '', false, true); // 30 days
        }

        AuditLogger::log('Admin Login Success', $admin['id'], null, ['username' => $username], 'User logged in successfully');
        Session::setFlash('success', 'Welcome back, ' . htmlspecialchars($admin['name']) . '!');

        $this->redirect('/dashboard');
    }

    public function logout(): void {
        Session::start();
        $adminId = Session::get('admin_id');

        if ($adminId) {
            Admin::updateRememberToken($adminId, null);
            AuditLogger::log('Admin Logout', $adminId, null, null, 'User logged out');
        }

        if (isset($_COOKIE['remember_me'])) {
            setcookie('remember_me', '', time() - 3600, '/');
        }

        Session::destroy();
        $this->redirect('/login');
    }

    public function showChangePassword(): void {
        \App\Middleware\AuthMiddleware::check();
        $this->render('auth.change_password');
    }

    public function changePassword(): void {
        \App\Middleware\AuthMiddleware::check();
        $this->validateCsrf();

        $currentPass = $_POST['current_password'] ?? '';
        $newPass     = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
            Session::setFlash('error', 'All password fields are required.');
            $this->redirect('/change-password');
        }

        if ($newPass !== $confirmPass) {
            Session::setFlash('error', 'New password and confirmation do not match.');
            $this->redirect('/change-password');
        }

        if (strlen($newPass) < 6) {
            Session::setFlash('error', 'New password must be at least 6 characters long.');
            $this->redirect('/change-password');
        }

        $adminId = Session::get('admin_id');
        $admin = Admin::findById($adminId);

        if (!$admin || !password_verify($currentPass, $admin['password_hash'])) {
            Session::setFlash('error', 'Current password is incorrect.');
            $this->redirect('/change-password');
        }

        $newHash = password_hash($newPass, PASSWORD_BCRYPT);
        Admin::updatePassword($adminId, $newHash);

        AuditLogger::log('Password Changed', $adminId, null, null, 'Admin updated password');
        Session::setFlash('success', 'Password updated successfully!');
        $this->redirect('/dashboard');
    }
}
