<?php

namespace BetoCampoy\CoraSdk;

use BetoCampoy\CoraSdk\Config\CoraConfig;
use BetoCampoy\CoraSdk\Exception\ApiException;
use BetoCampoy\CoraSdk\Exception\TransportException;
use BetoCampoy\CoraSdk\Util\Uuid;

class CoraClient
{
    private ?string $accessToken = null;
    private ?int $tokenExpiresAt = null; // timestamp

    public function __construct(
        private CoraConfig $config
    ) {
    }

    public static function fromEnv(): self
    {
        return new self(CoraConfig::fromEnv());
    }

    public function getConfig(): CoraConfig
    {
        return $this->config;
    }

    /**
     * Autenticação via client_credentials.
     */
    public function authenticate(bool $force = false): string
    {
        if (!$force && $this->accessToken !== null && $this->tokenExpiresAt !== null && $this->tokenExpiresAt > time()) {
            return $this->accessToken;
        }

        $endpoint = '/token';
        $payload = [
            'grant_type' => 'client_credentials',
            'client_id'  => $this->config->getClientId(),
        ];

        $response = $this->rawRequest('POST', $endpoint, $payload, false, true); // form-url-encoded

        $body = $response['body'];
        if (!isset($body['access_token'])) {
            throw new ApiException('access_token não encontrado na resposta.', $response['status'], $body);
        }

        $this->accessToken   = $body['access_token'];
        $expiresIn           = (int)($body['expires_in'] ?? 3600);
        $this->tokenExpiresAt = time() + $expiresIn - 60; // renova 1 min antes

        return $this->accessToken;
    }

    /**
     * Request JSON (default) com Bearer + mTLS + Idempotency.
     */
    public function request(string $method, string $endpoint, ?array $payload = null, bool $idempotent = null): array
    {
        // garante token
        $this->authenticate();

        return $this->rawRequest($method, $endpoint, $payload, true, false, $idempotent);
    }

    /**
     * Núcleo HTTP: usado tanto para /token quanto para as APIs.
     *
     * @return array{status:int, body:array|null}
     */
    private function rawRequest(
        string $method,
        string $endpoint,
        ?array $payload,
        bool $withAuth,
        bool $formUrlEncoded = false,
        ?bool $idempotent = null
    ): array {
        $url = rtrim($this->config->getBaseUrl(), '/') . $endpoint;

        $ch = curl_init($url);

        $headers = [];

        if ($formUrlEncoded) {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        } else {
            $headers[] = 'Content-Type: application/json';
        }

        if ($withAuth) {
            if ($this->accessToken === null) {
                throw new TransportException('Token de acesso não definido.');
            }
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
        }

        // idempotência por padrão em POST/PUT/PATCH
        $methodUpper = strtoupper($method);
        if ($idempotent === null) {
            $idempotent = in_array($methodUpper, ['POST', 'PUT', 'PATCH'], true);
        }
        if ($idempotent) {
            $headers[] = 'Idempotency-Key: ' . Uuid::v4();
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $methodUpper,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSLCERT        => $this->config->getCertPath(),
            CURLOPT_SSLKEY         => $this->config->getKeyPath(),
        ]);

        if ($payload !== null) {
            if ($formUrlEncoded) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            }
        }
        $body   = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($body === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new TransportException("Erro cURL: {$error}");
        }

        curl_close($ch);

        $decoded = null;
        if ($body !== '' && $body !== null) {
            $decoded = json_decode($body, true);
            if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new TransportException('Erro ao decodificar JSON: ' . json_last_error_msg());
            }
        }

        if ($status >= 400) {
            throw new ApiException("Erro de API Cora ({$status})", $status, $decoded);
        }

        return [
            'status' => $status,
            'body'   => $decoded,
        ];
    }
}
