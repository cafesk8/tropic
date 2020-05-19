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
     * @var \App\Component\Transfer\Pohoda\Response\PohodaAddressBookResponse|null
     */
    public $addressBookResponse;

    /**
     * @var int|null
     */
    public $eshopId;

    /**
     * @var string|null
     */
    public $priceIds;

    /**
     * @var \App\Component\Transfer\Pohoda\Customer\PohodaAddress|null
     */
    public $address;

    /**
     * @var \App\Component\Transfer\Pohoda\Customer\PohodaAddress|null
     */
    public $shipToAddress;

    /**
     * @return array
     */
    public function getAsArray(): array
    {
        return [
            'dataPackItemId' => $this->dataPackItemId,
            'addressBookResponse' => (array)$this->addressBookResponse,
            'eshopId' => $this->eshopId,
            'priceIds' => $this->priceIds,
            'address' => (array)$this->address,
            'shipToAddress' => (array)$this->shipToAddress,
        ];
    }
}
