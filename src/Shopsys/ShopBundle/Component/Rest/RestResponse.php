<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Rest;

class RestResponse
{
    /**
     * @var int
     */
    private $code;

    /**
     * @var string[]
     */
    private $data;

    /**
     * @param int $code
     * @param array|null $data
     */
    public function __construct(int $code, ?array $data)
    {
        $this->code = $code;
        $this->data = $data;
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @return string[]
     */
    public function getData(): array
    {
        return $this->data;
    }
}
