<?php

namespace App;

final class Security
{
    public static function csrf(): string
    {
        return $_SESSION['csrf'] ??= bin2hex(random_bytes(24));
    }

    public static function verifyCsrf(?string $token): bool
    {
        return is_string($token) && hash_equals($_SESSION['csrf'] ?? '', $token);
    }

    public static function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function excelSafe(string $value): string
    {
        return preg_match('/^[=+\-@]/u', $value) ? "'" . $value : $value;
    }

    public static function adminCredentials(?string $user = null, ?string $password = null): ?array
    {
        $user ??= getenv('ADMIN_USER') ?: '';
        $password ??= getenv('ADMIN_PASSWORD') ?: '';
        if (trim($user) === '' || trim($password) === '' || in_array($password, ['change-me', 'replace-this-password'], true)) return null;
        return [$user, $password];
    }
}
