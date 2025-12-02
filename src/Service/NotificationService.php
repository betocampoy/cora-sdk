<?php

namespace BetoCampoy\CoraSdk\Service;

use BetoCampoy\CoraSdk\CoraClient;

class NotificationService
{
    private const ENDPOINTS = '/notifications/endpoints';

    public function __construct(
        private CoraClient $client
    ) {
    }

    public function createEndpoint(string $url, string $resource, string $trigger): array
    {
        $payload = [
            'url'      => $url,
            'resource' => $resource, // ex: 'INVOICE'
            'trigger'  => $trigger,  // ex: 'STATUS_CHANGED'
        ];

        $response = $this->client->request('POST', self::ENDPOINTS, $payload);
        return $response['body'] ?? [];
    }

    public function listEndpoints(): array
    {
        $response = $this->client->request('GET', self::ENDPOINTS);
        return $response['body'] ?? [];
    }

    public function deleteEndpoint(string $id): void
    {
        $this->client->request('DELETE', self::ENDPOINTS . '/' . urlencode($id));
    }
}
