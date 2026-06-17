<?php

namespace App\Services;

class LoginAttemptService
{
    /**
     * Configuration (tune as you like)
     */
    public int $maxAttempts = 5;         // fail attempts
    public int $windowMinutes = 10;      // within this window
    public int $lockMinutes = 15;        // lock duration

    public function record(?int $userId, ?string $username, bool $success, string $reason = ''): void
    {
        $db = db_connect();
        $req = service('request');

        $db->table('login_attempts')->insert([
            'user_id'      => $userId,
            'username'     => $username,
            'ip_address'   => $req->getIPAddress(),
            'user_agent'   => substr((string) $req->getUserAgent(), 0, 255),
            'success'      => $success ? 1 : 0,
            'reason'       => $reason ?: null,
            'attempted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function isLocked(array $userRow): bool
    {
        if (empty($userRow['locked_until'])) return false;
        return strtotime($userRow['locked_until']) > time();
    }

    public function lockIfNeeded(int $userId, string $username): void
    {
        $db = db_connect();
        $since = date('Y-m-d H:i:s', time() - ($this->windowMinutes * 60));

        // Count failed attempts for this username in last window
        $fails = $db->table('login_attempts')
            ->where('success', 0)
            ->groupStart()
                ->where('user_id', $userId)
                ->orWhere('username', $username)
            ->groupEnd()
            ->where('attempted_at >=', $since)
            ->countAllResults();

        if ($fails < $this->maxAttempts) {
            return;
        }

        $lockedUntil = date('Y-m-d H:i:s', time() + ($this->lockMinutes * 60));

        $db->table('users')->where('id', $userId)->update([
            'locked_until' => $lockedUntil
        ]);

        // Audit lock event
        service('audit')->log('auth.lock', 'users', $userId, [
            'username' => $username,
            'locked_until' => $lockedUntil,
            'reason' => 'too_many_failed_attempts',
        ]);
    }

    public function clearLock(int $userId): void
    {
        db_connect()->table('users')->where('id', $userId)->update([
            'locked_until' => null
        ]);
    }

    public function clearExpiredLock(array $userRow): void
    {
        if (empty($userRow['locked_until'])) return;

        $untilTs = strtotime((string) $userRow['locked_until']);
        if ($untilTs !== false && $untilTs <= time()) {
            $this->clearLock((int) $userRow['id']);
        }
    }
}