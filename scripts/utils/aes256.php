<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';

/**
 * Encrypt a string with a password, using the AES-256-CBC method.
 * @param string $payload The string to be encrypted.
 * @param string $password The password.
 * @param bool $raw If this is true, returns the string in raw binary form, otherwise returns a hex.
 * @return string Returns the encrypted string.
 */
function aes256_encrypt(string $payload, string $password, bool $raw = true):string {
    $method = 'AES-256-CBC';
    $key = hash('sha256', $password, true);
    $iv = openssl_random_pseudo_bytes(16);
    $ciphertext = openssl_encrypt($payload, $method, $key, OPENSSL_RAW_DATA, $iv);
    $hash = hash_hmac('sha256', $ciphertext . $iv, $key, true);
    $result = $iv . $hash . $ciphertext;
    if (!$raw) $result = bin2hex($result);
    return $result;
}

/**
 * Decrypt a string a password, using the AES-256-CBC method.
 * @param string $payload The string to be decrypted. The payload must be in raw binary form.
 * @param string $password The password.
 * @return string|false Returns the decrypted string on success, otherwise returns false.
 */
function aes256_decrypt(string $payload, string $password):string|false {
    $method = 'AES-256-CBC';
    $iv = substr($payload, 0, 16);
    $hash = substr($payload, 16, 32);
    $ciphertext = substr($payload, 48);
    $key = hash('sha256', $password, true);
    if (!hash_equals(hash_hmac('sha256', $ciphertext . $iv, $key, true), $hash)) return false;
    return openssl_decrypt($ciphertext, $method, $key, OPENSSL_RAW_DATA, $iv);
}
