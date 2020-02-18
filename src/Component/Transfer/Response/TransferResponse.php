<?php

declare(strict_types=1);

namespace App\Component\Transfer\Response;

class TransferResponse
{
    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var \App\Component\Transfer\Response\TransferResponseItemDataInterface[]|null
     */
    private $responseData;

    /**
     * @param int $statusCode
     * @param \App\Component\Transfer\Response\TransferResponseItemDataInterface[] $responseData
     */
    public function __construct(int $statusCode, ?array $responseData = null)
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
     * @return \App\Component\Transfer\Response\TransferResponseItemDataInterface[]|null|mixed[]
     */
    public function getResponseData(): ?array
    {
        return $this->responseData;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->responseData === null || count($this->responseData) === 0;
    }
}
