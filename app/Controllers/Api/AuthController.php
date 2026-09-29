<?php

namespace App\Controllers\Api;

use App\Models\UserModel;
use App\Models\PasswordResetModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\RESTful\ResourceController;

class AuthController extends ResourceController
{
    protected $format = 'json';

    protected BaseConnection $db;
    protected UserModel $users;
    protected PasswordResetModel $passwordResetModel;

    /**
     * ==========================================
     * Constructor
     * ==========================================
     */
    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->users = new UserModel();
        $this->passwordResetModel = new PasswordResetModel();

        helper('security');
    }

    /**
     * ==========================================
     * Login
     * ==========================================
     */
    public function login()
    {
        $data = $this->request->getJSON(true) ?? [];

        // ---- Throttle ----
        $throttler = service('throttler');
        $ip = $this->request->getIPAddress();
        $usernameInput = (string) ($data['username'] ?? '');
        $key = 'login_' . sha1($ip . '|' . strtolower(trim($usernameInput)));

        if (! $throttler->check($key, 10, MINUTE)) {
            service('loginAttempts')->record(null, strtolower(trim($usernameInput)), false, 'throttled');
            service('audit')->log('auth.login_throttled', 'users', null, ['username' => $usernameInput]);

            return $this->respond([
                'status' => 429,
                'error' => 429,
                'messages' => ['error' => 'Too many login attempts. Please try again in a minute.'],
            ], 429);
        }

        // ---- Validation ----
        if (! $this->validateData($data, [
            'username' => 'required|min_length[3]|max_length[50]',
            'password' => 'required|min_length[6]|max_length[72]',
        ])) {
            return $this->respond([
                'status' => 422,
                'error' => 422,
                'messages' => [
                    'error' => 'Validation failed',
                    'fields' => $this->validator->getErrors(),
                ],
            ], 422);
        }

        $username = strtolower(trim((string) $data['username']));
        $password = (string) $data['password'];

        $user = $this->users->groupStart()
            ->where('username', $username)
            ->orWhere('email', $username)
            ->groupEnd()
            ->first();

        $loginAttempts = service('loginAttempts');

        if ($user) {
            $loginAttempts->clearExpiredLock($user);
            $user = $this->users->find($user['id']);
        }

        // ---- Check Lock ----
        if ($user && $loginAttempts->isLocked($user)) {
            $info = $this->lockRemaining($user);

            $loginAttempts->record((int) $user['id'], $username, false, 'locked');

            service('audit')->log('auth.login_failed', 'users', (int) $user['id'], [
                'username' => $username,
                'reason' => 'locked',
                'locked_until' => $user['locked_until'] ?? null,
                'remaining_minutes' => $info['remaining_minutes'],
            ]);

            return $this->respond([
                'status' => 403,
                'error' => 403,
                'messages' => ['error' => 'Account locked. Try again later.'],
                'lock' => $info,
            ], 403);
        }

        // ---- Verify Credentials ----
        // Verify against a dummy Argon2id hash when the user doesn't exist,
        // so response time doesn't reveal whether the account exists.
        $hash = $user['password_hash'] ?? '$argon2id$v=19$m=65536,t=4,p=1$aThUSlF0ay8xSnFZdmN1Yg$PGtlkSU1wzD2ALUydN/OLD34eZ6DftD76RtAqtkS+jY';
        $passwordValid = password_verify($password, $hash);

        if (! $user || ! $passwordValid) {
            $loginAttempts->record($user ? (int) $user['id'] : null, $username, false, 'invalid_credentials');

            if ($user) {
                $loginAttempts->lockIfNeeded((int) $user['id'], $username);
            }

            service('audit')->log('auth.login_failed', 'users', $user ? (int) $user['id'] : null, [
                'username' => $username,
                'reason' => 'invalid_credentials',
            ]);

            return $this->respond([
                'status' => 401,
                'error' => 401,
                'messages' => ['error' => 'Invalid credentials'],
            ], 401);
        }

        // ---- Active Check ----
        if (isset($user['is_active']) && (int) $user['is_active'] !== 1) {
            $loginAttempts->record((int) $user['id'], $username, false, 'inactive');

            service('audit')->log('auth.login_failed', 'users', (int) $user['id'], [
                'username' => $username,
                'reason' => 'inactive',
            ]);

            return $this->respond([
                'status' => 403,
                'error' => 403,
                'messages' => ['error' => 'Account is disabled'],
            ], 403);
        }

        // ---- Success ----
        $loginAttempts->record((int) $user['id'], $username, true, 'success');
        $loginAttempts->clearLock((int) $user['id']);

        session()->regenerate(true);
        session()->set([
            'uid' => (int) $user['id'],
            'username' => (string) $user['username'],
            'logged_in' => true,
        ]);

        service('audit')->log('auth.login', 'users', (int) $user['id'], [
            'username' => (string) $user['username'],
        ]);

        return $this->respond([
            'message' => 'Logged in',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'] ?? null,
                'firstname' => $user['firstname'] ?? null,
                'lastname' => $user['lastname'] ?? null,
            ],
        ]);
    }

    /**
     * ==========================================
     * Logout
     * ==========================================
     */
    public function logout()
    {
        $uid = (int) session('uid');

        service('audit')->log('auth.logout', 'users', $uid ?: null, [
            'username' => session('username'),
        ]);

        session()->destroy();

        return $this->respond(['message' => 'Logged out']);
    }

    /**
     * ==========================================
     * CSRF Token
     * ==========================================
     */
    public function csrf()
    {
        return $this->respond([
            'csrfToken' => csrf_hash(),
            'csrfName'  => csrf_token(),
        ]);
    }

    /**
     * ==========================================
     * Change Password
     * ==========================================
     */
    public function changePassword()
    {
        try {
            $uid = (int) session('uid');

            if (! $uid) {
                return $this->failUnauthorized('Not authenticated');
            }

            // ---- Throttle (limits guessing of the current password) ----
            if (! service('throttler')->check('chgpw_' . $uid, 5, 5 * MINUTE)) {
                service('audit')->log('auth.change_password_throttled', 'users', $uid, [
                    'ip' => $this->request->getIPAddress(),
                ]);

                return $this->respond([
                    'status' => 429,
                    'error' => 429,
                    'messages' => ['error' => 'Too many attempts. Please try again in a few minutes.'],
                ], 429);
            }

            $data = $this->request->getJSON(true) ?? [];

            if (! $this->validateData($data + ['username' => (string) session('username')], [
                'current_password' => 'required|min_length[6]|max_length[72]',
                'new_password'     => 'required|min_length[8]|max_length[72]|strong_password[username]',
                'confirm_password' => 'required|matches[new_password]',
            ])) {
                return $this->respond([
                    'status' => 422,
                    'error' => 422,
                    'messages' => [
                        'error' => 'Validation failed',
                        'fields' => $this->validator->getErrors(),
                    ],
                ], 422);
            }

            $user = $this->users->find($uid);

            if (! $user) {
                return $this->failNotFound('User not found');
            }

            $currentPassword = (string) $data['current_password'];
            $newPassword = (string) $data['new_password'];
            $now = date('Y-m-d H:i:s');

            if (! password_verify($currentPassword, (string) ($user['password_hash'] ?? ''))) {
                service('audit')->log('auth.change_password_failed', 'users', $uid, [
                    'reason' => 'invalid_current_password',
                    'ip'     => $this->request->getIPAddress(),
                ]);

                return $this->respond([
                    'status' => 400,
                    'error'  => 400,
                    'messages' => [
                        'error' => 'Current password is incorrect.',
                    ],
                ], 400);
            }

            if (password_verify($newPassword, (string) ($user['password_hash'] ?? ''))) {
                return $this->respond([
                    'status' => 422,
                    'error'  => 422,
                    'messages' => [
                        'error' => 'Validation failed',
                        'fields' => [
                            'new_password' => 'New password must be different from current password.',
                        ],
                    ],
                ], 422);
            }

            $this->db->transBegin();

            $passwordUpdated = $this->users->update($uid, [
                'password_hash' => password_hash($newPassword, PASSWORD_ARGON2ID),
                'updated_at'    => $now,
            ]);

            if (! $passwordUpdated) {
                throw new \RuntimeException('Failed to update password.');
            }

            $lockCleared = $this->db->table('users')
                ->where('id', $uid)
                ->update([
                    'locked_until' => null,
                    'updated_at'   => $now,
                ]);

            if ($lockCleared === false) {
                throw new \RuntimeException('Failed to clear user lock.');
            }

            $tokensInvalidated = $this->db->table('password_resets')
                ->where('user_id', $uid)
                ->where('used_at', null)
                ->set('used_at', $now)
                ->update();

            if ($tokensInvalidated === false) {
                throw new \RuntimeException('Failed to invalidate password reset tokens.');
            }

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed while changing password.');
            }

            $this->db->transCommit();

            revoke_user_sessions($uid, session_id());
            session()->regenerate(true);

            service('audit')->log('auth.change_password_success', 'users', $uid, [
                'ip' => $this->request->getIPAddress(),
            ]);

            return $this->respond([
                'message' => 'Password changed successfully.',
            ]);
        } catch (\Throwable $e) {
            $this->db->transRollback();

            log_message('error', 'Change Password Error: ' . $e->getMessage());

            return $this->respond([
                'status' => 500,
                'error' => 500,
                'messages' => [
                    'error' => 'Failed to change password.',
                    'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
                ],
            ], 500);
        }
    }

    /**
     * ==========================================
     * Lock Remaining Helper
     * ==========================================
     */
    private function lockRemaining(array $user): array
    {
        if (empty($user['locked_until'])) {
            return ['locked' => false, 'remaining_seconds' => 0, 'remaining_minutes' => 0, 'locked_until' => null];
        }

        $remaining = max(0, strtotime($user['locked_until']) - time());

        return [
            'locked' => $remaining > 0,
            'remaining_seconds' => $remaining,
            'remaining_minutes' => (int) ceil($remaining / 60),
            'locked_until' => $user['locked_until'],
        ];
    }

    /**
     * ==========================================
     * Forgot Password
     * ==========================================
     */
    public function forgotPassword()
    {
        try {
            $data = $this->request->getJSON(true) ?? [];

            if (! $this->validateData($data, [
                'email' => 'required|valid_email|max_length[150]',
            ])) {
                return $this->respond([
                    'status' => 422,
                    'error' => 422,
                    'messages' => [
                        'error' => 'Validation failed',
                        'fields' => $this->validator->getErrors(),
                    ],
                ], 422);
            }

            $email = strtolower(trim((string) $data['email']));

            // Throttle by IP + email to avoid abuse
            $throttler = service('throttler');
            $key = 'pwreset_' . sha1($this->request->getIPAddress() . '|' . $email);

            if (! $throttler->check($key, 5, MINUTE)) {
                return $this->respond([
                    'message' => 'If that email exists, we sent a password reset link.',
                ]);
            }

            $user = $this->users->where('LOWER(email)', $email)->first();

            // Always generic response to avoid enumeration
            if (! $user) {
                service('audit')->log('auth.password_reset_request', 'users', null, [
                    'email' => $email,
                    'result' => 'email_not_found',
                ]);

                return $this->respond([
                    'message' => 'If that email exists, we sent a password reset link.',
                ]);
            }

            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);

            $expireMinutes = (int) env('PASSWORD_RESET_EXPIRE_MINUTES', 30);
            $expiresAt = date('Y-m-d H:i:s', time() + ($expireMinutes * 60));
            $now = date('Y-m-d H:i:s');


            $this->db->transBegin();

            $invalidated = $this->db->table('password_resets')
                ->where('user_id', (int) $user['id'])
                ->where('used_at', null)
                ->set('used_at', $now)
                ->update();

            if ($invalidated === false) {
                throw new \RuntimeException('Failed to invalidate previous password reset tokens.');
            }

            $inserted = $this->passwordResetModel->insert([
                'user_id'    => (int) $user['id'],
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAt,
                'used_at'    => null,
                'ip_address' => $this->request->getIPAddress(),
                'user_agent' => substr((string) $this->request->getUserAgent(), 0, 255),
                'created_at' => $now,
            ]);

            if (! $inserted) {
                throw new \RuntimeException('Failed to create password reset token.');
            }

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed while creating password reset request.');
            }

            $this->db->transCommit();

            $frontend = rtrim((string) env('FRONTEND_URL', ''), '/');
            $resetLink = $frontend . '/reset-password?token=' . $token;

            $emailSvc = service('email');
            $emailSvc->setTo($user['email']);
            $brand = config('Branding');
            $appName = esc($brand->appName);
            $orgName = esc($brand->orgName);

            $emailSvc->setFrom((string) config('Email')->fromEmail, $brand->appName);
            $emailSvc->setSubject("Reset your {$brand->appName} password");

            $safeName = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
            $safeName = $safeName ?: $user['username'];

            $emailSvc->setMessage("
            <!DOCTYPE html>
            <html>
            <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Password Reset</title>
            </head>
            <body style='margin:0;padding:0;background:#f4f6f9;font-family:Arial,Helvetica,sans-serif;'>

            <table width='100%' cellpadding='0' cellspacing='0' style='padding:30px 0;background:#f4f6f9;'>
            <tr>
            <td align='center'>

            <table width='600' cellpadding='0' cellspacing='0' style='background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);'>

            <tr>
            <td style='background:#2563eb;color:#ffffff;padding:20px 30px;font-size:20px;font-weight:bold;text-align:center;'>
            {$appName} Password Reset
            </td>
            </tr>

            <tr>
            <td style='padding:30px;color:#333333;font-size:14px;line-height:1.6;'>

            <p>Hello <strong>{$safeName}</strong>,</p>

            <p>
            We received a request to reset your password for your <strong>{$appName}</strong> account.
            Click the button below to set a new password.
            </p>

            <p style='text-align:center;margin:30px 0;'>
            <a href='{$resetLink}'
            style='background:#2563eb;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:6px;font-weight:bold;display:inline-block;'>
            Reset Password
            </a>
            </p>

            <p>
            This reset link will expire in <strong>{$expireMinutes} minutes</strong>.
            </p>

            <p>
            If the button above does not work, copy and paste the following link into your browser:
            </p>

            <p style='word-break:break-all;color:#2563eb;'>
            {$resetLink}
            </p>

            <p>
            If you did not request a password reset, you can safely ignore this email.
            Your password will remain unchanged.
            </p>

            </td>
            </tr>

            <tr>
            <td style='background:#f1f3f5;padding:20px;font-size:12px;color:#666;text-align:center;'>
            <p style='margin:0;'>This email was sent automatically by {$appName}.</p>
            <p style='margin:5px 0 0 0;'>{$orgName}</p>
            </td>
            </tr>

            </table>

            </td>
            </tr>
            </table>

            </body>
            </html>
            ");

            $sent = $emailSvc->send(false);

            service('audit')->log('auth.password_reset_request', 'users', (int) $user['id'], [
                'email' => $email,
                'sent' => $sent ? 1 : 0,
                'expires_at' => $expiresAt,
            ]);

            return $this->respond([
                'message' => 'If that email exists, we sent a password reset link.',
            ]);
        } catch (\Throwable $e) {
            $this->db->transRollback();

            log_message('error', 'Forgot Password Error: ' . $e->getMessage());

            return $this->respond([
                'message' => 'If that email exists, we sent a password reset link.',
            ]);
        }
    }

    /**
     * ==========================================
     * Reset Password
     * ==========================================
     */
    public function resetPassword()
    {
        try {
            // ---- Throttle ----
            $ip = $this->request->getIPAddress();

            if (! service('throttler')->check('resetpw_' . sha1($ip), 10, 5 * MINUTE)) {
                service('audit')->log('auth.password_reset_throttled', 'users', null, ['ip' => $ip]);

                return $this->respond([
                    'status' => 429,
                    'error' => 429,
                    'messages' => ['error' => 'Too many attempts. Please try again in a few minutes.'],
                ], 429);
            }

            $data = $this->request->getJSON(true) ?? [];

            if (! $this->validateData($data, [
                'token' => 'required|min_length[20]',
                'password' => 'required|min_length[8]|max_length[72]|strong_password[username]',
                'password_confirm' => 'required|matches[password]',
            ])) {
                return $this->respond([
                    'status' => 422,
                    'error' => 422,
                    'messages' => [
                        'error' => 'Validation failed',
                        'fields' => $this->validator->getErrors(),
                    ],
                ], 422);
            }

            $token = (string) $data['token'];
            $tokenHash = hash('sha256', $token);

            $row = $this->passwordResetModel->where('token_hash', $tokenHash)->first();

            if (! $row) {
                service('audit')->log('auth.password_reset_failed', 'users', null, [
                    'reason' => 'invalid_token',
                ]);

                return $this->respond([
                    'status' => 400,
                    'error' => 400,
                    'messages' => ['error' => 'Invalid or expired reset link.'],
                ], 400);
            }

            if (! empty($row['used_at'])) {
                service('audit')->log('auth.password_reset_failed', 'users', (int) $row['user_id'], [
                    'reason' => 'token_used',
                ]);

                return $this->respond([
                    'status' => 400,
                    'error' => 400,
                    'messages' => ['error' => 'This reset link has already been used.'],
                ], 400);
            }

            if (strtotime((string) $row['expires_at']) <= time()) {
                $markedUsed = $this->passwordResetModel->update((int) $row['id'], [
                    'used_at' => date('Y-m-d H:i:s'),
                ]);

                if (! $markedUsed) {
                    log_message('error', 'Reset Password Error: failed to mark expired token as used. Token ID: ' . (int) $row['id']);
                }

                service('audit')->log('auth.password_reset_failed', 'users', (int) $row['user_id'], [
                    'reason' => 'token_expired',
                ]);

                return $this->respond([
                    'status' => 400,
                    'error' => 400,
                    'messages' => ['error' => 'Invalid or expired reset link.'],
                ], 400);
            }

            $userId = (int) $row['user_id'];
            $newPassword = (string) $data['password'];
            $now = date('Y-m-d H:i:s');

            $this->db->transBegin();

            $passwordUpdated = $this->users->update($userId, [
                'password_hash' => password_hash($newPassword, PASSWORD_ARGON2ID),
                'updated_at' => $now,
            ]);

            if (! $passwordUpdated) {
                throw new \RuntimeException('Failed to update user password.');
            }

            $tokenMarkedUsed = $this->passwordResetModel->update((int) $row['id'], [
                'used_at' => $now,
            ]);

            if (! $tokenMarkedUsed) {
                throw new \RuntimeException('Failed to mark reset token as used.');
            }

            $lockCleared = $this->db->table('users')
                ->where('id', $userId)
                ->update([
                    'locked_until' => null,
                    'updated_at'   => $now,
                ]);

            if ($lockCleared === false) {
                throw new \RuntimeException('Failed to clear user lock.');
            }

            $otherTokensInvalidated = $this->db->table('password_resets')
                ->where('user_id', $userId)
                ->where('used_at', null)
                ->set('used_at', $now)
                ->update();

            if ($otherTokensInvalidated === false) {
                throw new \RuntimeException('Failed to invalidate remaining password reset tokens.');
            }

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed while resetting password.');
            }

            $this->db->transCommit();

            revoke_user_sessions($userId);

            service('audit')->log('auth.password_reset_success', 'users', $userId, [
                'ip' => $this->request->getIPAddress(),
            ]);

            return $this->respond([
                'message' => 'Password has been reset successfully. Please login.',
            ]);
        } catch (\Throwable $e) {
            $this->db->transRollback();

            log_message('error', 'Reset Password Error: ' . $e->getMessage());

            return $this->respond([
                'status' => 500,
                'error' => 500,
                'messages' => [
                    'error' => 'Failed to reset password.',
                    'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
                ],
            ], 500);
        }
    }
}
