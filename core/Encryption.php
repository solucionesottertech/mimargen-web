<?php
/**
 * Encryption
 *
 * AES-256-CBC encryption for sensitive data at rest.
 * Matches OtterErp Encryption contract.
 *
 * Usage:
 *   $enc = new Encryption(APP_SECRET);
 *   $cipher = $enc->encrypt($plaintext);
 *   $plain  = $enc->decrypt($cipher);
 */
class Encryption
{
    private string $key;
    private string $method = 'aes-256-cbc';

    /**
     * @param string $secret Application secret (used to derive encryption key)
     */
    public function __construct(string $secret)
    {
        // Derive a 32-byte key from the secret
        $this->key = hash('sha256', $secret, true);
    }

    /**
     * Encrypt a string. Returns base64-encoded "iv:ciphertext".
     *
     * @param string $plaintext
     * @return string
     */
    public function encrypt(string $plaintext): string
    {
        $ivLen = openssl_cipher_iv_length($this->method);
        $iv = openssl_random_pseudo_bytes($ivLen);
        if ($iv === false) {
            throw new \RuntimeException('Failed to generate IV');
        }

        $ciphertext = openssl_encrypt($plaintext, $this->method, $this->key, OPENSSL_RAW_DATA, $iv);
        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed');
        }

        return base64_encode($iv . ':' . $ciphertext);
    }

    /**
     * Decrypt a base64-encoded "iv:ciphertext" string.
     *
     * @param string $payload
     * @return string
     */
    public function decrypt(string $payload): string
    {
        $decoded = base64_decode($payload, true);
        if ($decoded === false) {
            throw new \RuntimeException('Invalid payload encoding');
        }

        $parts = explode(':', $decoded, 2);
        if (count($parts) !== 2) {
            throw new \RuntimeException('Invalid payload format');
        }

        [$iv, $ciphertext] = $parts;

        $plaintext = openssl_decrypt($ciphertext, $this->method, $this->key, OPENSSL_RAW_DATA, $iv);
        if ($plaintext === false) {
            throw new \RuntimeException('Decryption failed');
        }

        return $plaintext;
    }
}
