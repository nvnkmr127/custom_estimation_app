<?php

namespace Tests\Feature;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Tests\TestCase;

class JwtVerificationTest extends TestCase
{
    public function test_firebase_jwt_rs256_verification_works(): void
    {
        // Generate a temporary RS256 key pair for testing
        $res = openssl_pkey_new([
            "digest_alg" => "sha256",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($res, $privateKey);
        $details = openssl_pkey_get_details($res);
        $publicKey = $details['key'];

        $payload = [
            'iss' => 'auth-core',
            'aud' => 'custom-estimation-app',
            'sub' => '1234567890',
            'email' => 'test@example.com',
            'iat' => time(),
            'exp' => time() + 3600
        ];

        // Encode using private key
        $jwt = JWT::encode($payload, $privateKey, 'RS256');

        // Decode using public key
        $decoded = JWT::decode($jwt, new Key($publicKey, 'RS256'));

        $this->assertEquals('test@example.com', $decoded->email);
        $this->assertEquals('auth-core', $decoded->iss);
    }
}
