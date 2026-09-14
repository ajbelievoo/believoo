<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class EncryptionService
{
    private string $cipher = 'AES-256-CBC';
    private string $key;
    
    public function __construct()
    {
        $this->key = hash('sha256', config('app.key'), true);
    }
    
    public function encrypt(string $data): string
    {
        try {
            $ivLength = openssl_cipher_iv_length($this->cipher);
            $iv = openssl_random_pseudo_bytes($ivLength);
            
            $encrypted = openssl_encrypt(
                $data,
                $this->cipher,
                $this->key,
                OPENSSL_RAW_DATA,
                $iv
            );
            
            if ($encrypted === false) {
                throw new \Exception('Encryption failed: ' . openssl_error_string());
            }
            
            $combined = $iv . $encrypted;
            return base64_encode($combined);
            
        } catch (\Exception $e) {
            Log::error('Encryption failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    public function decrypt(string $encryptedData): string
    {
        try {
            $decoded = base64_decode($encryptedData, true);
            
            if ($decoded === false) {
                throw new \Exception('Invalid base64 encoded data');
            }
            
            $ivLength = openssl_cipher_iv_length($this->cipher);
            $iv = substr($decoded, 0, $ivLength);
            $encrypted = substr($decoded, $ivLength);
            
            $decrypted = openssl_decrypt(
                $encrypted,
                $this->cipher,
                $this->key,
                OPENSSL_RAW_DATA,
                $iv
            );
            
            if ($decrypted === false) {
                throw new \Exception('Decryption failed');
            }
            
            return $decrypted;
            
        } catch (\Exception $e) {
            Log::error('Decryption failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    public function generateSecurePassword(int $length = 16): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        $maxIndex = strlen($chars) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $maxIndex)];
        }
        
        return $password;
    }
}
