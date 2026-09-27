<?php

namespace App\Models;

use App\Config\Database;

class Admin {

    public static function findByUsername(string $username): ?array {
        return Database::fetchOne("SELECT * FROM admin WHERE username = :username LIMIT 1", [
            ':username' => $username
        ]);
    }

    public static function findById(int $id): ?array {
        return Database::fetchOne("SELECT * FROM admin WHERE id = :id LIMIT 1", [
            ':id' => $id
        ]);
    }

    public static function updateLastLogin(int $id, string $sessionId): void {
        Database::execute(
            "UPDATE admin SET last_login = NOW(), session_id = :session_id WHERE id = :id",
            [':session_id' => $sessionId, ':id' => $id]
        );
    }

    public static function updateRememberToken(int $id, ?string $token): void {
        Database::execute(
            "UPDATE admin SET remember_token = :token WHERE id = :id",
            [':token' => $token, ':id' => $id]
        );
    }

    public static function findByRememberToken(string $token): ?array {
        return Database::fetchOne(
            "SELECT * FROM admin WHERE remember_token = :token LIMIT 1",
            [':token' => $token]
        );
    }

    public static function updatePassword(int $id, string $newPasswordHash): bool {
        return Database::execute(
            "UPDATE admin SET password_hash = :hash, remember_token = NULL WHERE id = :id",
            [':hash' => $newPasswordHash, ':id' => $id]
        );
    }
}
