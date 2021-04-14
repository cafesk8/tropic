<?php

declare(strict_types=1);

namespace App\Component\LuigisBox;

class LuigisBoxApiKeysProvider
{
    /**
     * @var string[][]
     */
    private array $keys;

    /**
     * @param string[][] $keys
     */
    public function __construct(array $keys)
    {
        $this->keys = $keys;
    }

    /**
     * @param string $locale
     * @return string
     */
    public function getPrivateKey(string $locale): string
    {
        return $this->keys[$locale]['private'] ?? '';
    }

    /**
     * Public key is also sometimes called Tracker ID
     *
     * @param string $locale
     * @return string
     */
    public function getPublicKey(string $locale): string
    {
        return $this->keys[$locale]['public'] ?? '';
    }
}
