# Changelog – Cora SDK

Todas as mudanças relevantes deste projeto serão documentadas aqui.

O formato segue o padrão "Keep a Changelog".

---

## [0.1.2] – 2025-12-03
### Adicionado
- Suporte nativo à geração de QRCode Pix.
- Criação do serviço `PixQrCodeGenerator`, encapsulando a biblioteca `endroid/qr-code`.
- Método `dataUriFromEmv()` retorna o QRCode pronto para uso em `<img src="">`.
- Método `pngFromEmv()` retorna o binário PNG do QRCode.
- Documentação atualizada no README.md com exemplos em PHP puro e Symfony.
- Compatibilidade total com ambientes legados sem dependências externas.
- Suporte automático à instalação via Composer com a dependência `endroid/qr-code`.

### Alterado
- Melhoria no README.md com novo formato usando `~~~` para evitar conflitos no ChatGPT.
- Ajustes no namespace e organização da pasta `Service/`.

### Corrigido
- N/A

---

## [0.1.1] – 2025-12-01
### Adicionado
- Melhor tratamento de exceções no mTLS.
- Melhoria na documentação de certificados A1/A3.
- Ajuste no carregamento de variáveis de ambiente via `CoraConfig::fromEnv()`.

### Alterado
- Refatoração interna de `CoraClient` para maior estabilidade.
- Ajuste no timeout padrão (para evitar desconexões indevidas no Windows).

### Corrigido
- Erro em ambientes Windows ao carregar certificados PFX sem cadeia intermediária.

---

## [0.1.0] – 2025-11-30
### Adicionado
- Primeira versão funcional do SDK.
- Suporte completo a mTLS usando certificado PFX/P12.
- Classe `CoraConfig` com suporte a variáveis de ambiente.
- Classe `CoraClient` responsável por requisições autenticadas.
- `InvoiceService` com:
    - `createInvoice`
    - `createBoleto`
    - `getInvoice`
    - `cancelInvoice`
- Estrutura PSR-4.
- Licença MIT incluída.
- README inicial com instruções básicas.

---

## Estrutura de versionamento
O projeto segue **Semantic Versioning (SemVer)**:

- **MAJOR**: Mudanças incompatíveis
- **MINOR**: Funcionalidades novas compatíveis
- **PATCH**: Correções e melhorias internas

---

## Futuras versões (roadmap)
- Transferências bancárias (TED/PIX).
- Pagamento de boletos (payments).
- Extrato bancário via API.
- Webhooks e assinatura de mensagens.
- Mock-server para desenvolvimento offline.
- Bundle oficial para Symfony (`betocampoy/cora-bundle`).
- Testes unitários e integração contínua.

---

## Notas
Se encontrar problemas ou desejar contribuir, abra uma issue ou envie um PR.  
Criado e mantido por **Beto Campoy**.
