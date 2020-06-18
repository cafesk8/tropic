<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Response;

class PohodaOrderResponse extends PohodaResponse
{
    /**
     * @var int|null
     */
    public ?int $producedDetailId;
}
