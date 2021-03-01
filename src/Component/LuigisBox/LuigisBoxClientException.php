<?php

declare(strict_types=1);

namespace App\Component\LuigisBox;

use Exception;

class LuigisBoxClientException extends Exception
{
    /**
     * @var string[]
     */
    private array $objectUrls;

    /**
     * @param string $message
     * @param string[] $objectUrls
     */
    public function __construct(string $message, array $objectUrls)
    {
        $this->objectUrls = $objectUrls;
        parent::__construct($message);
    }

    /**
     * @return string[]
     */
    public function getObjectUrls(): array
    {
        return $this->objectUrls;
    }
}
