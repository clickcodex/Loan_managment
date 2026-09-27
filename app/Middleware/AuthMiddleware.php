<?php

namespace App\Middleware;

use App\Helpers\Session;
use App\Models\Admin;

class AuthMiddleware {

    public static function check(): bool {
        Session::start();

        // 1. Check active session
        if (Session::has('admin_id')) {
            return true;
        }

        // 2. Check Remember Me cookie
        if (isset($_COOKIE['remember_me'])) {
            $token = $_COOKIE['remember_me'];
            $admin = Admin::findByRememberToken($token);

            if ($admin) {
                // Restore session
                Session::set('admin_id', $admin['id']);
                Session::set('admin_user', $admin);
                return true;
            } else {
                // Invalid cookie - clear it
                setcookie('remember_me', '', time() - 3600, '/');
            }
        }

        // 3. Not authenticated -> Redirect to login
        Session::setFlash('error', 'Please log in to access the system.');
        $baseUrl = defined('BASE_URL') ? BASE_URL : '';
        header("Location: " . $baseUrl . "/login");
        exit;
    }
}
