<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Response;

class PohodaResponse
{
    public const POHODA_XML_RESPONSE_ITEM_STATE_OK = 'ok';

    public ?string $responsePackItemId;

    public ?string $responsePackItemState;

    public ?string $responsePackItemNote;

    public ?string $responseState;

    /**
     * @var string[]
     */
    public array $responseNotes = [];
}
