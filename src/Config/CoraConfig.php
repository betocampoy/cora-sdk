<?php

namespace BetoCampoy\CoraSdk\Config;

class CoraConfig
{
    public const ENV_SANDBOX = 'sandbox';
    public const ENV_PRODUCTION = 'production';

    public function __construct(
        private string $clientId,
        private string $certPath,
        private string $keyPath,
        private string $environment = self::ENV_SANDBOX,
    ) {
        if (!in_array($environment, [self::ENV_SANDBOX, self::ENV_PRODUCTION], true)) {
            throw new \InvalidArgumentException("Ambiente inválido: {$environment}");
        }
    }

    public static function fromEnv(): self
    {
        $clientId    = getenv('CORA_CLIENT_ID') ?: '';
        $certPath    = getenv('CORA_CERT_PATH') ?: '';
        $keyPath     = getenv('CORA_KEY_PATH') ?: '';
        $environment = getenv('CORA_ENV') ?: self::ENV_SANDBOX;

        if ($clientId === '' || $certPath === '' || $keyPath === '') {
            throw new \RuntimeException('Variáveis de ambiente CORA_CLIENT_ID, CORA_CERT_PATH e CORA_KEY_PATH são obrigatórias.');
        }

        return new self($clientId, $certPath, $keyPath, $environment);
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getCertPath(): string
    {
        return $this->certPath;
    }

    public function getKeyPath(): string
    {
        return $this->keyPath;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function getBaseUrl(): string
    {
        // Integração Direta usa base matls-clients tanto para Stage quanto para Prod
        return $this->environment === self::ENV_PRODUCTION
            ? 'https://matls-clients.api.cora.com.br'
            : 'https://matls-clients.api.stage.cora.com.br';
    }
}
