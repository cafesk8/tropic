<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Mall;

use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Pricing\PriceCalculation;
use Shopsys\FrameworkBundle\Model\Pricing\Vat\Vat;
use Shopsys\FrameworkBundle\Model\Pricing\Vat\VatDataFactory;
use Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFactoryInterface;

class MallImportPriceCalculatorCalculation
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Vat\VatDataFactoryInterface
     */
    private $vatDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFactoryInterface
     */
    private $vatFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\PriceCalculation
     */
    private $priceCalculation;

    /**
     * PriceCalculatorMallImportCalculation constructor.
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Vat\VatDataFactory $vatDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFactoryInterface $vatFactory
     * @param \Shopsys\FrameworkBundle\Model\Pricing\PriceCalculation $priceCalculation
     */
    public function __construct(VatDataFactory $vatDataFactory, VatFactoryInterface $vatFactory, PriceCalculation $priceCalculation)
    {
        $this->vatFactory = $vatFactory;
        $this->priceCalculation = $priceCalculation;
        $this->vatDataFactory = $vatDataFactory;
    }

    /**
     * @param string $percentVat
     * @param string $priceWithVat
     * @param int $quantity
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    public function calculatePrice(string $percentVat, string $priceWithVat, int $quantity = 1): Price
    {
        $vat = $this->getVat($percentVat);

        $totalPriceWithVat = Money::create($priceWithVat)->multiply($quantity);
        $totalVatAmount = $this->priceCalculation->getVatAmountByPriceWithVat($totalPriceWithVat, $vat);
        $totalPriceWithoutVat = $totalPriceWithVat->subtract($totalVatAmount);

        return new Price($totalPriceWithoutVat, $totalPriceWithVat);
    }

    /**
     * @param string $percentVat
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Vat\Vat
     */
    public function getVat(string $percentVat): Vat
    {
        $vatData = $this->vatDataFactory->create();
        $vatData->name = 'orderItemVat';
        $vatData->percent = $percentVat;

        return $this->vatFactory->create($vatData);
    }
}
