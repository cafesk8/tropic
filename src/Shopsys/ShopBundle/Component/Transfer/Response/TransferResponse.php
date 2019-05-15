<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Transfer\Response;

class TransferResponse
{
    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface[]
     */
    private $responseData;

    /**
     * @param int $statusCode
     * @param \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface[] $responseData
     */
    public function __construct(int $statusCode, array $responseData)
    {
        $this->statusCode = $statusCode;
        $this->responseData = $responseData;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface[]
     */
    public function getResponseData(): array
    {
        return $this->responseData;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return count($this->responseData) === 0;
    }
}
