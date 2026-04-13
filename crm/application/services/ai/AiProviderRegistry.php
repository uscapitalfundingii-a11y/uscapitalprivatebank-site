<?php

namespace app\services\ai;

use app\services\ai\Contracts\AiProviderInterface;
use app\services\ai\Providers\Base44SuperagentProvider;
use RuntimeException;

defined('BASEPATH') or exit('No direct script access allowed');

class AiProviderRegistry
{
    /**
     * @var array<string, AiProviderInterface>
     */
    private static array $providers = [];
    private static bool $booted = false;

    /**
     * Register a new AI provider with a unique name.
     *
     * @param string $identifier
     * @param AiProviderInterface $provider
     */
    public static function registerProvider(string $identifier, AiProviderInterface $provider): void
    {
        self::$providers[$identifier] = $provider;
    }

    private static function bootProviders(): void
    {
        if (self::$booted) {
            return;
        }

        self::$booted = true;

        if (! isset(self::$providers['base44_superagent'])) {
            self::registerProvider('base44_superagent', new Base44SuperagentProvider());
        }
    }

    /**
     * Retrieve an AI provider by its name.
     *
     * @param string $identifier
     * @return AiProviderInterface
     */
    public static function getProvider(string $identifier): AiProviderInterface
    {
        self::bootProviders();

        if (! isset(self::$providers[$identifier]) && isset(self::$providers['base44_superagent'])) {
            return self::$providers['base44_superagent'];
        }

        if (!isset(self::$providers[$identifier])) {
            throw new RuntimeException("AI provider not found: $identifier");
        }

        return self::$providers[$identifier];
    }

    /**
     * Get all registered providers.
     *
     * @return array<int, array{identifier: string, provider: AiProviderInterface}>
     */
    public static function getAllProviders(): array
    {
        self::bootProviders();

        return collect(self::$providers)
            ->mapWithKeys(function (AiProviderInterface $provider, string $identifier) {
                return [$identifier => [
                    'id' => $identifier,
                    'name' => $provider->getName(),
                    'provider' => $provider,
                ]
                ];
            })
            ->toArray();
    }
}

