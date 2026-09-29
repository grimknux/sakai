<?php

namespace App\Validation;

/**
 * Password strength rule, used only when a password is being set
 * (create user, change password, reset password), never on login.
 *
 * Usage: always 'strong_password[username]' (CI4 calls parameterless rules with a
 * different signature). The parameter names
 * the field holding the username; if absent, the username check is skipped.
 */
class PasswordRules
{
    private const COMMON = [
        'password', 'password1', 'password12', 'password123', 'passw0rd', 'p@ssw0rd', 'p@ssword',
        '12345678', '123456789', '1234567890', '11111111', '00000000', '87654321', 'qwertyui',
        'qwerty123', 'qwertyuiop', 'abc12345', 'abcd1234', 'abc123456', 'iloveyou', 'admin123',
        'admin1234', 'welcome1', 'welcome123', 'letmein1', 'changeme', 'changeme1', 'default123',
        'football1', 'monkey123', 'dragon123', 'sunshine1', 'princess1', 'superman1', 'master123',
        'login123', 'test1234', 'user1234', 'sakai123', 'doh12345', 'ilocos123',
    ];

    public function strong_password(?string $str, ?string $params, array $data, ?string &$error = null): bool
    {
        $str = (string) $str;

        if (! preg_match('/[A-Za-z]/', $str) || ! preg_match('/\d/', $str)) {
            $error = 'Password must contain at least one letter and one number.';

            return false;
        }

        if (in_array(strtolower($str), self::COMMON, true)) {
            $error = 'This password is too common. Choose a different one.';

            return false;
        }

        $username = $params !== null && $params !== '' ? strtolower(trim((string) ($data[$params] ?? ''))) : '';

        if (strlen($username) >= 3 && str_contains(strtolower($str), $username)) {
            $error = 'Password must not contain your username.';

            return false;
        }

        return true;
    }
}
