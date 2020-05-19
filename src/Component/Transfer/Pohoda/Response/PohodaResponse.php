<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Response;

class PohodaResponse
{
    public const POHODA_XML_RESPONSE_ITEM_STATE_OK = 'ok';

    /**
     * @var string|null
     */
    public $responsePackItemId;

    /**
     * @var string|null
     */
    public $responsePackItemState;

    /**
     * @var string|null
     */
    public $responsePackItemNote;
}
