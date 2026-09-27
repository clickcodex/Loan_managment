<?php

namespace App\Helpers;

use App\Config\Database;

class AuditLogger {

    public static function log(
        string $action,
        ?int $userId = 1,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $details = null
    ): void {
        try {
            Session::start();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            if ($ip === '::1') $ip = '127.0.0.1';
            $sessionId = session_id() ?: null;

            $sql = "INSERT INTO audit_logs (action, user_id, ip_address, session_id, old_values, new_values, details, created_at)
                    VALUES (:action, :user_id, :ip, :session_id, :old_val, :new_val, :details, NOW())";

            Database::execute($sql, [
                ':action'     => $action,
                ':user_id'    => $userId ?: 1,
                ':ip'         => $ip,
                ':session_id' => $sessionId,
                ':old_val'    => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                ':new_val'    => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
                ':details'    => $details
            ]);
        } catch (\Exception $e) {
            error_log("Failed to record audit log: " . $e->getMessage());
        }
    }

    public static function computeFieldDiff(?array $oldValues, ?array $newValues): array {
        if (empty($oldValues) || empty($newValues)) {
            return [];
        }

        $diffs = [];
        $ignoreKeys = ['created_at', 'updated_at', 'csrf_token', 'password', 'password_hash'];

        foreach ($newValues as $key => $newVal) {
            if (in_array($key, $ignoreKeys)) continue;

            if (array_key_exists($key, $oldValues)) {
                $oldVal = $oldValues[$key];

                // Convert arrays/JSON for comparison
                $oldValStr = is_array($oldVal) ? json_encode($oldVal) : (string)$oldVal;
                $newValStr = is_array($newVal) ? json_encode($newVal) : (string)$newVal;

                if ($oldValStr !== $newValStr) {
                    $label = ucwords(str_replace(['_', '-'], ' ', $key));
                    $diffs[] = [
                        'field' => $key,
                        'label' => $label,
                        'old'   => $oldValStr === '' ? '—' : $oldValStr,
                        'new'   => $newValStr === '' ? '—' : $newValStr
                    ];
                }
            }
        }

        return $diffs;
    }
}
