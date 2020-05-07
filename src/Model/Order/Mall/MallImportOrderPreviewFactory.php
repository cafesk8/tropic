<?php

declare(strict_types=1);

namespace App\Model\Order\Mall;

use App\Component\Domain\DomainHelper;
use App\Model\Order\Item\QuantifiedProduct;
use App\Model\Order\OrderData;
use App\Model\Order\Preview\OrderPreview;
use App\Model\Order\Preview\OrderPreviewFactory;
use App\Model\Pricing\Currency\CurrencyFacade;
use App\Model\Product\ProductFacade;
use MPAPI\Entity\Order;
use Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice;

class MallImportOrderPreviewFactory
{
    /**
     * @var \App\Model\Order\Preview\OrderPreviewFactory
     */
    private $orderPreviewFactory;

    /**
     * @var \App\Model\Order\Mall\MallImportPriceCalculatorCalculation
     */
    private $mallImportPriceCalculatorCalculation;

    /**
     * @var \App\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \App\Model\Pricing\Currency\CurrencyFacade
     */
    private $currencyFacade;

    /**
     * @param \App\Model\Order\Preview\OrderPreviewFactory $orderPreviewFactory
     * @param \App\Model\Order\Mall\MallImportPriceCalculatorCalculation $mallImportPriceCalculatorCalculation
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
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
     * @param \App\Model\Order\OrderData $orderData
     * @return \App\Model\Order\Preview\OrderPreview
     */
    public function creteOrderPreview(Order $mallOrderDetail, OrderData $orderData): OrderPreview
    {
        $quantifiedProducts = $this->getQuantifiedProducts($mallOrderDetail);

        $orderPreview = $this->orderPreviewFactory->create(
            $this->currencyFacade->findByCode($mallOrderDetail->getCurrencyId()),
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
                $mallOrderItem[Order::KEY_ITEM_QUANTITY]
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
