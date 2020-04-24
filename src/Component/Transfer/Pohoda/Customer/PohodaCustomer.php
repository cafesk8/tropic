<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Customer;

class PohodaCustomer
{
    /**
     * @var string|null
     */
    public $dataPackItemId;

    /**
     * @var int|null
     */
    public $eshopId;

    /**
     * @var \App\Component\Transfer\Pohoda\Customer\PohodaAddress|null
     */
    public $address;

    /**
     * @var \App\Component\Transfer\Pohoda\Customer\PohodaAddress|null
     */
    public $shipToAddress;
}
