<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JwtService
{
    /**
     * Chave secreta para assinar/validar tokens
     */
    private $secret;

    /**
     * Algoritmo usado para assinar tokens
     */
    private $algorithm = 'HS256';

    /**
     * TTL (Time To Live) do token em segundos
     * 24 horas = 86400 segundos
     */
    private $ttl = 86400;

    public function __construct()
    {
        // Usar a chave de aplicação do Laravel como chave secreta
        $this->secret = env('APP_KEY', 'base64:secret-key');

        // Se a chave começar com 'base64:', decodificar
        if (strpos($this->secret, 'base64:') === 0) {
            $this->secret = base64_decode(substr($this->secret, 7));
        }
    }

    /**
     * Gerar um novo JWT token
     *
     * @param array $payload Dados a serem inclusos no token
     * @return string Token JWT
     */
    public function generate(array $payload): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->ttl;

        $data = array_merge($payload, [
            'iat' => $issuedAt,    // Issued At
            'exp' => $expire,      // Expiration Time
            'iss' => env('APP_URL', 'http://localhost'), // Issuer
        ]);

        return JWT::encode($data, $this->secret, $this->algorithm);
    }

    /**
     * Validar e decodificar um JWT token
     *
     * @param string $token Token JWT
     * @return object|null Payload decodificado ou null se inválido
     */
    public function validate(string $token): ?object
    {
        try {
            // Remover "Bearer " do header se estiver lá
            $token = str_replace('Bearer ', '', $token);

            // Decodificar e validar
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));

            return $decoded;
        } catch (Exception $e) {
            // Token inválido, expirado ou com erro de assinatura
            return null;
        }
    }

    /**
     * Extrair o payload de um token sem validar a assinatura
     *
     * @param string $token Token JWT
     * @return object|null Payload ou null se inválido
     */
    public function decode(string $token): ?object
    {
        try {
            $token = str_replace('Bearer ', '', $token);

            // Usar JWT::decode com "verify" desabilitado
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));

            return $decoded;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Verificar se um token está expirado
     *
     * @param string $token Token JWT
     * @return bool True se expirado, false caso contrário
     */
    public function isExpired(string $token): bool
    {
        $payload = $this->decode($token);

        if (!$payload) {
            return true;
        }

        return isset($payload->exp) && $payload->exp < time();
    }
}
