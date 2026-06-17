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