<?php

declare(strict_types=1);

namespace App\Component\Cofidis;

use App\Component\Cofidis\Exception\CofidisNotConfiguredException;

class CofidisClientFactory
{
    /**
     * @var array
     */
    private $config;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $locale
     * @return \App\Component\Cofidis\CofidisClient
     */
    public function createByLocale(string $locale): CofidisClient
    {
        return new CofidisClient($this->getConfigByLocale($locale));
    }

    /**
     * @param string $locale
     * @return array
     */
    private function getConfigByLocale(string $locale): array
    {
        $configByLocale = $this->config[$locale];
        $this->config = array_merge($this->config, $configByLocale);

        if ($this->config['merchant_id'] === null) {
            throw new CofidisNotConfiguredException('Cofidis is not configured!');
        }

        return $this->config;
    }
}
