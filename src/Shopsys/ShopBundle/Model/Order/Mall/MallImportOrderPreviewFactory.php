<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Mall;

use MPAPI\Entity\Order;
use Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice;
use Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedProduct;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Model\Order\OrderData;
use Shopsys\ShopBundle\Model\Order\Preview\OrderPreview;
use Shopsys\ShopBundle\Model\Order\Preview\OrderPreviewFactory;
use Shopsys\ShopBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\ShopBundle\Model\Product\ProductFacade;

class MallImportOrderPreviewFactory
{
    /**
     * @var \Shopsys\ShopBundle\Model\Order\Preview\OrderPreviewFactory
     */
    private $orderPreviewFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\Mall\MallImportPriceCalculatorCalculation
     */
    private $mallImportPriceCalculatorCalculation;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Pricing\Currency\CurrencyFacade
     */
    private $currencyFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Preview\OrderPreviewFactory $orderPreviewFactory
     * @param \Shopsys\ShopBundle\Model\Order\Mall\MallImportPriceCalculatorCalculation $mallImportPriceCalculatorCalculation
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     * @param \Shopsys\ShopBundle\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     */
    public function __construct(
        OrderPreviewFactory $orderPreviewFactory,
        MallImportPriceCalculatorCalculation $mallImportPriceCalculatorCalculation,
        ProductFacade $productFacade,
        CurrencyFacade $currencyFacade
    ) {
        $this->orderPreviewFactory = $orderPreviewFactory;
        $this->mallImportPriceCalculatorCalculation = $mallImportPriceCalculatorCalculation;
        $this->productFacade = $productFacade;
        $this->currencyFacade = $currencyFacade;
    }

    /**
     * @param \MPAPI\Entity\Order $mallOrderDetail
     * @param \Shopsys\ShopBundle\Model\Order\OrderData $orderData
     * @return \Shopsys\ShopBundle\Model\Order\Preview\OrderPreview
     */
    public function creteOrderPreview(Order $mallOrderDetail, OrderData $orderData): OrderPreview
    {
        $quantifiedProducts = $this->getQuantifiedProducts($mallOrderDetail);

        $orderPreview = $this->orderPreviewFactory->create(
            $this->currencyFacade->findByCode($mallOrderDetail[Order::KEY_CURRENCY_ID]),
            DomainHelper::CZECH_DOMAIN,
            $quantifiedProducts,
            $orderData->transport,
            $orderData->payment
        );

        $orderPreview->setQuantifiedItemsPricesByIndex($this->getQuantifiedProductsPrice($mallOrderDetail));
        return $orderPreview;
    }

    /**
     * @param \MPAPI\Entity\Order $mallOrderDetail
     * @return \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedProduct[]
     */
    public function getQuantifiedProducts(Order $mallOrderDetail): array
    {
        return array_map(function (array $mallOrderItem) {
            return new QuantifiedProduct(
                $this->productFacade->getById($mallOrderItem[Order::KEY_ID]),
                $mallOrderItem[Order::KEY_ID]
            );
        }, $mallOrderDetail->getItems());
    }

    /**
     * @param \MPAPI\Entity\Order $mallOrderDetail
     * @return \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice[]
     */
    public function getQuantifiedProductsPrice(Order $mallOrderDetail): array
    {
        return array_map(function (array $mallOrderItem) {
            return new QuantifiedItemPrice(
                $this->mallImportPriceCalculatorCalculation->calculatePrice(strval($mallOrderItem[Order::KEY_ITEM_VAT]), strval($mallOrderItem[Order::KEY_ITEM_PRICE])),
                $this->mallImportPriceCalculatorCalculation->calculatePrice(strval($mallOrderItem[Order::KEY_ITEM_VAT]), strval($mallOrderItem[Order::KEY_ITEM_PRICE]), $mallOrderItem[Order::KEY_ITEM_QUANTITY]),
                $this->mallImportPriceCalculatorCalculation->getVat(strval($mallOrderItem[Order::KEY_ITEM_VAT]))
            );
        }, $mallOrderDetail->getItems());
    }
}
