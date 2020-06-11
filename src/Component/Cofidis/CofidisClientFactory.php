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
     * @return \App\Component\Cofidis\CofidisClient
     */
    public function create(): CofidisClient
    {
        if ($this->config['merchant_id'] === null) {
            throw new CofidisNotConfiguredException('Cofidis is not configured!');
        }

        return new CofidisClient($this->config);
    }
}
