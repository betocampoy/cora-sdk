<?php

namespace BetoCampoy\CoraSdk\Service;

use BetoCampoy\CoraSdk\CoraClient;

class InvoiceService
{
    private const INVOICES_ENDPOINT = '/v2/invoices';
    private const INVOICES_PAY_ENDPOINT  = '/v2/invoices/pay';

    public function __construct(
        private CoraClient $client
    ) {
    }

    /**
     * Cria uma cobrança (boleto, boleto+pix, pix).
     * O tipo depende do payload conforme documentação da Cora.
     */
    public function createInvoice(array $payload): array
    {
        $response = $this->client->request('POST', self::INVOICES_ENDPOINT, $payload);
        return $response['body'] ?? [];
    }

    /**
     * Azulejo mais explícito para "boleto".
     * Você pode ajustar aqui defaults específicos de boleto, se quiser.
     */
    public function createBoleto(array $payload): array
    {
        if(array_key_exists('payment_forms', $payload)) {
            unset($payload['payment_forms']);
        }
        $payload['payment_forms'] = ['BANK_SLIP'];
        return $this->createInvoice($payload);
    }

    /**
     * Idem para Pix/QR Code Pix.
     * Se a Cora exigir algum campo específico de Pix,
     * você pode complementar/validar aqui.
     */
    public function createPix(array $payload): array
    {
        if(array_key_exists('payment_forms', $payload)) {
            unset($payload['payment_forms']);
        }
        $payload['payment_forms'] = ['PIX'];
        return $this->createInvoice($payload);
    }

    public function getInvoice(string $invoiceId): array
    {
        $endpoint = self::INVOICES_ENDPOINT . '/' . urlencode($invoiceId);
        $response = $this->client->request('GET', $endpoint);
        return $response['body'] ?? [];
    }

    /**
     * Listagem com filtros (state, start, end, etc.)
     * Exemplo de uso:
     *   listInvoices(['state' => 'PAID', 'start' => '2025-11-01', 'end' => '2025-11-30'])
     */
    public function listInvoices(array $query = []): array
    {
        $endpoint = self::INVOICES_ENDPOINT;

        if (!empty($query)) {
            $endpoint .= '?' . http_build_query($query);
        }

        $response = $this->client->request('GET', $endpoint);
        return $response['body'] ?? [];
    }

    /**
     * Pagar boleto/QR Code em ambiente de Stage.
     *
     * - `invoiceId` é o ID retornado na emissão do boleto (/v2/invoices).
     * - A Cora exige o header Idempotency-Key, que pode ser gerado no CoraClient
     *   (mantendo o padrão de não passar headers aqui).
     */
    public function payInvoiceInStage(string $invoiceId): array
    {
        $payload = [
            'id' => $invoiceId,
        ];

        $response = $this->client->request('POST', self::INVOICES_PAY_ENDPOINT, $payload);

        return $response['body'] ?? [];
    }

    /**
     * Cancela uma cobrança (boleto ou pix) na Cora.
     *
     * Endpoint oficial:
     *   DELETE /v2/invoices/{invoice_id}
     *
     * Observações:
     * - Sucesso → HTTP 204 No Content
     * - Se a cobrança já estiver paga, a Cora retorna erro (ex.: 422)
     * - Não envia body
     *
     * @throws \Exception caso o cancelamento falhe
     */
    public function cancelInvoice(string $invoiceId): bool
    {
        $endpoint = self::INVOICES_ENDPOINT . '/' . urlencode($invoiceId);

        // DELETE não envia payload
        $response = $this->client->request('DELETE', $endpoint);

        // A Cora retorna HTTP 204 quando o cancelamento foi feito com sucesso.
        $status = $response['status'] ?? null;

        if ($status === 204) {
            return true;
        }

        // Se não for 204, então houve erro.
        // A Cora costuma retornar JSON com detalhes do erro.
        $body = $response['body'] ?? [];

        throw new \RuntimeException(sprintf(
            'Erro ao cancelar invoice "%s". Status: %s. Resposta: %s',
            $invoiceId,
            $status,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        ));
    }

}
