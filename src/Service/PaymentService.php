<?php

namespace BetoCampoy\CoraSdk\Service;

use BetoCampoy\CoraSdk\CoraClient;
use BetoCampoy\CoraSdk\Config\CoraConfig;
use BetoCampoy\CoraSdk\Util\Uuid;

class PaymentService
{
    public function __construct(
        private CoraClient $client
    ) {
    }

    private function getApiBaseUrl(): string
    {
        $env = $this->client->getConfig()->getEnvironment();

        return $env === CoraConfig::ENV_PRODUCTION
            ? 'https://api.cora.com.br'
            : 'https://api.stage.cora.com.br';
    }

    /**
     * Inicia o pagamento de um boleto em Stage/Prod (API payments/initiate).
     *
     * @param string      $code           Identificador interno da operação (idempotente).
     * @param string      $digitableLine  Linha digitável do boleto.
     * @param string|null $scheduledAt    Data agendada (YYYY-MM-DD). Se null, pode usar hoje.
     */
    public function initiateBoletoPayment(string $code, string $digitableLine, ?string $scheduledAt = null): array
    {
        // garante token válido
        $accessToken = $this->client->authenticate();

        $url = $this->getApiBaseUrl() . '/payments/initiate';

        $payload = [
            'code'           => $code,
            'digitable_line' => $digitableLine,
        ];

        if ($scheduledAt !== null) {
            $payload['scheduled_at'] = $scheduledAt;
        }

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Idempotency-Key: ' . Uuid::v4(),
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => json_encode($payload),
        ]);

        $body   = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($body === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException("Erro cURL ao chamar payments/initiate: {$error}");
        }

        curl_close($ch);

        $decoded = json_decode($body, true);
        if ($decoded === null && $body !== '' && json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Erro ao decodificar JSON da resposta de payments/initiate: ' . json_last_error_msg());
        }

        if ($status >= 400) {
            throw new \RuntimeException("Erro da API Cora payments/initiate ({$status}): " . $body);
        }

        return $decoded ?? [];
    }
}
