<?php

namespace BetoCampoy\CoraSdk\Service;

use BetoCampoy\CoraSdk\CoraClient;

class BankStatementService
{
    private const STATEMENT_ENDPOINT = '/bank-statement/statement';

    public function __construct(
        private CoraClient $client
    ) {
    }

    /**
     * Consulta extrato entre duas datas (YYYY-MM-DD).
     * Pode incluir paginação (page, size) se desejar.
     */
    public function getStatement(string $start, string $end, ?int $page = null, ?int $size = null): array
    {
        $query = [
            'start' => $start,
            'end'   => $end,
        ];

        if ($page !== null) {
            $query['page'] = $page;
        }
        if ($size !== null) {
            $query['size'] = $size;
        }

        $endpoint = self::STATEMENT_ENDPOINT . '?' . http_build_query($query);

        $response = $this->client->request('GET', $endpoint);
        return $response['body'] ?? [];
    }
}
