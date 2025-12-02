# Cora SDK – Integração PHP com o Banco Cora
**by Beto Campoy**

SDK oficial (não-oficial 😄) para integração PHP com a API do Banco Cora, incluindo suporte completo a **mTLS com certificado A1/A3**, criação de **boletos**, **PIX**, consultas e futuros endpoints do ecossistema financeiro Cora.

Este SDK foi projetado para funcionar **tanto em PHP puro (legado)** quanto em **aplicações modernas com Symfony**, oferecendo uma camada consistente, simples e modular sobre as APIs da Cora.

---

## 📦 Instalação

Via Composer:

~~~bash
composer require betocampoy/cora-sdk
~~~

---

## ⚙️ Configuração

O SDK utiliza uma classe central chamada `CoraConfig`, que recebe todas as configurações necessárias:

- Client ID
- Client Secret
- Certificado A1/A3 (PFX/P12)
- Senha do certificado
- URL da API (stage/prod)
- URL mTLS
- Timeout

Você pode configurar manualmente:

~~~php
use BetoCampoy\CoraSdk\CoraConfig;

$config = new CoraConfig(
    clientId: 'seu-client-id',
    clientSecret: 'seu-client-secret',
    certPath: '/caminho/do/certificado.pfx',
    certPassword: 'senha-cert',
    baseUrl: 'https://api.stage.cora.com.br',
    matlsBaseUrl: 'https://matls-clients.api.stage.cora.com.br',
);
~~~

Ou automaticamente via variáveis de ambiente:

~~~php
$config = CoraConfig::fromEnv();
~~~

### Variáveis de ambiente suportadas

~~~text
CORA_CLIENT_ID=
CORA_CLIENT_SECRET=
CORA_CERT_PATH=
CORA_CERT_PASSWORD=
CORA_BASE_URL=https://api.stage.cora.com.br
CORA_MATLS_BASE_URL=https://matls-clients.api.stage.cora.com.br
CORA_TIMEOUT=30
~~~

---

## 🔐 Conexão Segura (mTLS)

A API da Cora exige **Autenticação Mútua TLS (mTLS)**.

Isso significa que:

1. O servidor envia seu certificado SSL (como em qualquer HTTPS).
2. O cliente **também** precisa enviar um certificado (PFX/P12) válido.
3. O `CoraClient` configura automaticamente o cURL para usar esse certificado.

Erros comuns de certificado são convertidos para exceções específicas:

- `TransportException` — falhas de rede, cURL, SSL, certificado, timeout etc.
- `ApiException` — a API respondeu com erro HTTP (4xx / 5xx), com status code e body disponíveis.

---

## 🔧 Uso — PHP Puro (Legado)

Exemplo de criação de boleto:

~~~php
use BetoCampoy\CoraSdk\CoraConfig;
use BetoCampoy\CoraSdk\CoraClient;
use BetoCampoy\CoraSdk\Service\InvoiceService;
use BetoCampoy\CoraSdk\Exceptions\ApiException;
use BetoCampoy\CoraSdk\Exceptions\TransportException;

$config = CoraConfig::fromEnv();
$client = new CoraClient($config);
$invoiceService = new InvoiceService($client);

$payload = [
    "code" => "mensalidade_123_2025-11",
    "amount" => 19990, // em centavos
    "description" => "Mensalidade Minha Encomenda - Novembro",
    "customer" => [
        "name" => "Transportadora XPTO",
        "document" => "12345678000155",
        "email" => "financeiro@empresa.com",
    ],
    // demais campos conforme documentação da Cora
];

try {
    $invoice = $invoiceService->createBoleto($payload);

    // Exemplo: acessar campos retornados
    // $invoice['id'], $invoice['digitable_line'], $invoice['qr_code'], etc.
    print_r($invoice);
} catch (ApiException $e) {
    echo "Erro API Cora ({$e->getStatusCode()}): " . $e->getMessage();
    var_dump($e->getResponseBody());
} catch (TransportException $e) {
    echo "Erro de transporte/SSL: " . $e->getMessage();
}
~~~

---

## 🧰 Uso — Symfony

Registrando os serviços no `services.yaml`:

~~~yaml
services:
    BetoCampoy\CoraSdk\CoraConfig:
        factory: ['BetoCampoy\CoraSdk\CoraConfig', 'fromEnv']

    BetoCampoy\CoraSdk\CoraClient:
        arguments:
            $config: '@BetoCampoy\CoraSdk\CoraConfig'

    BetoCampoy\CoraSdk\Service\InvoiceService:
        arguments:
            $client: '@BetoCampoy\CoraSdk\CoraClient'
~~~

Usando em um serviço da aplicação:

~~~php
use BetoCampoy\CoraSdk\Service\InvoiceService;

class MonthlyBillingService
{
    public function __construct(
        private InvoiceService $invoiceService,
    ) {}

    public function gerarCobranca(/* ... */): void
    {
        $payload = [
            // montar payload da cobrança aqui
        ];

        $invoice = $invoiceService->createBoleto($payload);

        // persistir dados da cobrança retornada etc.
    }
}
~~~

---

## 🧾 Endpoints disponíveis (v0.1.0)

### `InvoiceService`

- `createInvoice(array $payload): array`  
  Cria uma cobrança genérica (boleto, pix, boleto+pix) conforme o payload da Cora.

- `createBoleto(array $payload): array`  
  Alias semântico para criação de boleto usando `createInvoice`.

- `getInvoice(string $invoiceId): array`  
  Consulta detalhes de uma cobrança.

- `cancelInvoice(string $invoiceId, ?array $payload = null): array`  
  Solicita o cancelamento de uma cobrança (se suportado pela API).

---

## 🗂 Estrutura do Projeto

~~~text
src/
  CoraConfig.php
  CoraClient.php
  Exceptions/
    ApiException.php
    TransportException.php
  Service/
    InvoiceService.php
composer.json
README.md
~~~

---

## 🚨 Troubleshooting (erros comuns)

### ❌ "could not load PEM client certificate"

Possíveis causas:

- Caminho do certificado (`CORA_CERT_PATH`) inválido.
- Permissões de leitura do arquivo.
- Formato incompatível ou corrompido.

### ❌ "schannel: next InitializeSecurityContext failed"

Mais comum em Windows quando:

- O certificado contém cadeia completa que o sistema não aceita.
- Falta de permissões do usuário para acessar o certificado.

Sugestões:

- Exportar o PFX novamente com cadeias limitadas.
- Testar antes via `curl` na linha de comando com o mesmo certificado.

### ❌ HTTP 400 / 401 / 403 (ApiException)

Verifique:

- Se o client-id/client-secret são do ambiente correto (stage vs produção).
- Se o payload enviado está idêntico ao exemplo da documentação da Cora.
- Se os escopos do client permitem o endpoint utilizado.

---

## 🗺 Roadmap

- [ ] Transferências
- [ ] Pagamento de boletos (payments)
- [ ] Extrato bancário
- [ ] Webhooks + verificação de assinatura
- [ ] Bundle Symfony dedicado (`betocampoy/cora-bundle`)
- [ ] Testes automatizados com PHPUnit
- [ ] Mock server local para desenvolvimento

---

## 📄 Licença

Licenciado sob a licença **MIT** — uso livre para projetos pessoais e comerciais.

---

## ✨ Autor

**Beto Campoy**  
Criador do SDK e responsável pela integração com sistemas como Minha Encomenda, Amo e Quero Vinho, OrganizzeMe, entre outros.
