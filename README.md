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


---

## 🔐 Conexão Segura (mTLS)

O SDK configura automaticamente cURL com:

- Certificado cliente PFX/P12
- Senha do certificado
- Auth mTLS bidirecional
- Timeout configurável

Erros são convertidos para exceções:

- `TransportException` → falhas de rede / SSL
- `ApiException` → erros HTTP retornados pela Cora

---

## 🧰 Uso em PHP puro

Criando cobrança:

~~~php
$config = CoraConfig::fromEnv();
$client = new CoraClient($config);

$invoiceService = new InvoiceService($client);

$invoice = $invoiceService->createInvoice([
    "code" => "mensal_123",
    "amount" => 19990,
    "description" => "Mensalidade",
    "customer" => [
        "name" => "Transportadora XPTO",
        "document" => "12345678000155",
        "email" => "financeiro@empresa.com"
    ]
]);
~~~

---

# ✨ NOVO EM v0.1.2 — GERAÇÃO NATIVA DE QR CODE PIX

O SDK agora inclui o serviço **PixQrCodeGenerator**, que encapsula automaticamente o pacote `endroid/qr-code`.

Você passa **somente o EMV** retornado pela Cora → e ele devolve diretamente a **Data URI** para `<img src="">`.

---

## 📌 Exemplo em PHP puro

~~~php
use BetoCampoy\CoraSdk\Service\PixQrCodeGenerator;

$qr = new PixQrCodeGenerator();

$emv = $invoice['pix']['emv']; // retornado pela Cora

$dataUri = $qr->dataUriFromEmv($emv);

echo '<img src="' . $dataUri . '" />';
~~~

---

## 📌 Exemplo em Symfony (Controller)

~~~php
$qrcode = $pixQrCodeGenerator->dataUriFromEmv($invoice['pix']['emv']);

return $this->render('billing/pix.html.twig', [
    'qrcode' => $qrcode
]);
~~~

Twig:

~~~twig
<img src="{{ qrcode }}" alt="Pix QR Code" class="img-fluid" />
~~~

---

## 🧩 Serviço PixQrCodeGenerator

~~~php
class PixQrCodeGenerator
{
    public function __construct(
        private int $defaultSize = 700,
        private int $defaultMargin = 5
    ) {}

    public function dataUriFromEmv(string $emv, ?int $size = null, ?int $margin = null): string
    {
        $builder = new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            validateResult: false,
            data: $emv,
            size: $size ?? $this->defaultSize,
            margin: $margin ?? $this->defaultMargin,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        return $builder->build()->getDataUri();
    }

    public function pngFromEmv(string $emv): string
    {
        return $builder->build()->getString();
    }
}
~~~

---

## 🧾 Endpoints disponíveis (v0.1.2)

### InvoiceService
- `createInvoice(array $payload): array`
- `createBoleto(array $payload): array`
- `getInvoice(string $invoiceId): array`
- `cancelInvoice(string $invoiceId): array`

### PixQrCodeGenerator
- `dataUriFromEmv(string $emv): string`
- `pngFromEmv(string $emv): string`

---

## 🚨 Troubleshooting

### ❌ "could not load PEM client certificate"
- Caminho incorreto
- Permissões
- Certificado corrompido

### ❌ "schannel: next InitializeSecurityContext failed"
- Problemas de cadeia PFX no Windows

### ❌ HTTP 400 / 401 / 403
- Client ID/Secret incorretos
- Ambiente errado (stage vs production)
- Payload fora do padrão Cora

---

## 🗺 Roadmap

- [x] QRCode Pix nativo
- [ ] Transferências
- [ ] Pagamento de boletos
- [ ] Extrato bancário
- [ ] Webhooks
- [ ] Symfony Bundle oficial
- [ ] Testes automatizados
- [ ] Mock server local

---

## 📄 Licença

MIT

---

## ✨ Autor

Beto Campoy
