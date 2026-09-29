<?php

function encrypt_id($id)
{
    $encrypter = \Config\Services::encrypter();
    // Encrypt the ID
    $ciphertext = $encrypter->encrypt((string)$id);
    
    // Convert to URL-safe Base64
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($ciphertext));
}

function decrypt_id($encodedId)
{
    $encrypter = \Config\Services::encrypter();
    
    // Reverse the URL-safe Base64
    $decoded = base64_decode(str_replace(['-', '_'], ['+', '/'], $encodedId));
    
    try {
        return $encrypter->decrypt($decoded);
    } catch (\Exception $e) {
        // Return null if decryption fails (tampered or invalid ID)
        return null;
    }
}

/**
 * Delete every stored session belonging to a user (database session driver).
 * Pass the raw session_id() to keep the current one alive (rows are stored as "<cookieName>:<id>").
 */
function revoke_user_sessions(int $userId, ?string $exceptSessionId = null): void
{
    $builder = \Config\Database::connect()->table(config('Session')->savePath);

    $builder->groupStart()
        ->like('data', "uid|i:{$userId};", 'after')
        ->orLike('data', ";uid|i:{$userId};", 'both')
        ->groupEnd();

    if ($exceptSessionId !== null) {
        $builder->where('id !=', config('Session')->cookieName . ':' . $exceptSessionId);
    }

    $builder->delete();
}
