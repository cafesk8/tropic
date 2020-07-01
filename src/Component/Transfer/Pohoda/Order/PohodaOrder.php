<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Order;

use App\Component\Transfer\Pohoda\Customer\PohodaAddress;
use App\Component\Transfer\Pohoda\Response\PohodaOrderResponse;
use DateTime;
use Shopsys\FrameworkBundle\Component\Money\Money;

class PohodaOrder
{
    /**
     * @var string|null
     */
    public ?string $dataPackItemId;

    /**
     * @var \App\Component\Transfer\Pohoda\Response\PohodaOrderResponse|null
     */
    public ?PohodaOrderResponse $orderResponse;

    /**
     * @var string|null
     */
    public ?string $number;

    /**
     * @var int|null
     */
    public ?int $eshopId;

    /**
     * @var \DateTime|null
     */
    public ?DateTime $date;

    /**
     * @var int|null
     */
    public ?int $status;

    /**
     * @var int|null
     */
    public ?int $customerEshopId;

    /**
     * @var \App\Component\Transfer\Pohoda\Customer\PohodaAddress|null
     */
    public ?PohodaAddress $address;

    /**
     * @var \App\Component\Transfer\Pohoda\Customer\PohodaAddress|null
     */
    public ?PohodaAddress $shipToAddress;

    /**
     * @var string|null
     */
    public ?string $pohodaPaymentName;

    /**
     * @var string|null
     */
    public ?string $pricingGroup;

    /**
     * @var \App\Component\Transfer\Pohoda\Order\PohodaOrderItem[]
     */
    public array $orderItems;

    /**
     * @var \App\Component\Transfer\Pohoda\Order\PohodaCurrency|null
     */
    public ?PohodaCurrency $currency;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    public ?Money $totalPriceWithVat;

    /**
     * @var string|null
     */
    public ?string $pohodaTransportId;

    /**
     * @var string|null
     */
    public ?string $internalNote;

    public function __construct()
    {
        $this->orderResponse = null;
    }

    /**
     * @return array
     */
    public function getAsArray(): array
    {
        return [
            'dataPackItemId' => $this->dataPackItemId,
            'orderResponse' => (array)$this->orderResponse,
            'number' => $this->number,
            'eshopId' => $this->eshopId,
            'date' => $this->date,
            'status' => $this->status,
            'customerEshopId' => $this->customerEshopId,
            'address' => (array)$this->address,
            'shipToAddress' => (array)$this->shipToAddress,
            'pohodaPaymentName' => $this->pohodaPaymentName,
            'pricingGroup' => $this->pricingGroup,
            'orderItems' => (array)$this->orderItems,
            'currency' => $this->currency,
            'totalPriceWithVat' => $this->totalPriceWithVat,
            'pohodaTransportId' => $this->pohodaTransportId,
            'internalNote' => $this->internalNote,
        ];
    }
}
