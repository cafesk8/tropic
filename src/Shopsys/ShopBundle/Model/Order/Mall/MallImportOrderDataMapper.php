<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Mall;

use DateTime;
use MPAPI\Entity\Order;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItemData;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItemDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Order\OrderDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Order\Status\OrderStatus;
use Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusFacade;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Model\Country\CountryFacade;
use Shopsys\ShopBundle\Model\Order\Mall\Exception\NoMallPaymentTypeExistException;
use Shopsys\ShopBundle\Model\Order\Mall\Exception\NoTransportForMallIdException;
use Shopsys\ShopBundle\Model\Order\OrderData;
use Shopsys\ShopBundle\Model\Payment\Payment;
use Shopsys\ShopBundle\Model\Payment\PaymentFacade;
use Shopsys\ShopBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\ShopBundle\Model\Product\ProductFacade;
use Shopsys\ShopBundle\Model\Transport\Transport;
use Shopsys\ShopBundle\Model\Transport\TransportFacade;

class MallImportOrderDataMapper
{
    private const CZECH_LOCALE = DomainHelper::CZECH_LOCALE;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Order\OrderDataFactoryInterface
     */
    private $orderDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Order\Item\OrderItemDataFactoryInterface
     */
    private $orderItemDataFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Country\CountryFacade
     */
    private $countryFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Payment\PaymentFacade
     */
    private $paymentFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Transport\TransportFacade
     */
    private $transportFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusFacade
     */
    private $orderStatusFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Pricing\Currency\CurrencyFacade
     */
    private $currencyFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\Mall\MallImportPriceCalculatorCalculation
     */
    private $mallImportPriceCalculatorCalculation;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderDataFactoryInterface $orderDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\OrderItemDataFactoryInterface $orderItemDataFactoryInterface
     * @param \Shopsys\ShopBundle\Model\Country\CountryFacade $countryFacade
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     * @param \Shopsys\ShopBundle\Model\Payment\PaymentFacade $paymentFacade
     * @param \Shopsys\ShopBundle\Model\Transport\TransportFacade $transportFacade
     * @param \Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusFacade $orderStatusFacade
     * @param \Shopsys\ShopBundle\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \Shopsys\ShopBundle\Model\Order\Mall\MallImportPriceCalculatorCalculation $mallImportPriceCalculatorCalculation
     */
    public function __construct(
        OrderDataFactoryInterface $orderDataFactory,
        OrderItemDataFactoryInterface $orderItemDataFactoryInterface,
        CountryFacade $countryFacade,
        ProductFacade $productFacade,
        PaymentFacade $paymentFacade,
        TransportFacade $transportFacade,
        OrderStatusFacade $orderStatusFacade,
        CurrencyFacade $currencyFacade,
        MallImportPriceCalculatorCalculation $mallImportPriceCalculatorCalculation
    ) {
        $this->orderDataFactory = $orderDataFactory;
        $this->orderItemDataFactory = $orderItemDataFactoryInterface;
        $this->countryFacade = $countryFacade;
        $this->productFacade = $productFacade;
        $this->paymentFacade = $paymentFacade;
        $this->transportFacade = $transportFacade;
        $this->orderStatusFacade = $orderStatusFacade;
        $this->currencyFacade = $currencyFacade;
        $this->mallImportPriceCalculatorCalculation = $mallImportPriceCalculatorCalculation;
    }

    /**
     * @param \MPAPI\Entity\Order $mallOrderDetail
     * @return \Shopsys\ShopBundle\Model\Order\OrderData
     */
    public function createOrderDataFromMallOrderDetail(Order $mallOrderDetail): OrderData
    {
        /** @var \Shopsys\ShopBundle\Model\Order\OrderData $orderData */
        $orderData = $this->orderDataFactory->create();

        $orderData->orderNumber = null;
        $orderData->status = $this->orderStatusFacade->getById(OrderStatus::TYPE_NEW);

        $orderData->mallOrderId = $mallOrderDetail->getOrderId();
        $orderData->mallStatus = $mallOrderDetail->getStatus();

        $mallCustomerNameSplitBySpace = explode(' ', $mallOrderDetail->getName());
        $orderData->firstName = $mallCustomerNameSplitBySpace[0];
        $orderData->lastName = $mallCustomerNameSplitBySpace[array_key_last($mallCustomerNameSplitBySpace)];
        $orderData->email = $mallOrderDetail->getEmail();
        $orderData->telephone = $mallOrderDetail->getPhone();
        $orderData->companyName = $mallOrderDetail->getCompany();
        $orderData->companyNumber = null;
        $orderData->companyTaxNumber = null;
        $orderData->street = $mallOrderDetail->getStreet();
        $orderData->city = $mallOrderDetail->getCity();
        $orderData->postcode = $mallOrderDetail->getZip();
        $country = $this->countryFacade->getByCode($mallOrderDetail->getCountry());
        $orderData->country = $country;

        $orderData->deliveryAddressSameAsBillingAddress = true;
        $orderData->deliveryFirstName = $mallCustomerNameSplitBySpace[0];
        $orderData->deliveryLastName = $mallCustomerNameSplitBySpace[array_key_last($mallCustomerNameSplitBySpace)];
        $orderData->deliveryCompanyName = $mallOrderDetail->getCompany();
        $orderData->deliveryTelephone = $mallOrderDetail->getPhone();
        $orderData->deliveryStreet = $mallOrderDetail->getStreet();
        $orderData->deliveryCity = $mallOrderDetail->getCity();
        $orderData->deliveryPostcode = $mallOrderDetail->getZip();
        $orderData->deliveryCountry = $country;

        $orderData->note = null;
        $orderItemsWithoutTransportAndPaymentData = [];
        foreach ($mallOrderDetail->getItems() as $mallOrderItem) {
            $orderItemsWithoutTransportAndPaymentData[] = $this->createOrderItem($mallOrderItem);
        }
        $orderData->itemsWithoutTransportAndPayment = $orderItemsWithoutTransportAndPaymentData;
        $orderData->createdAt = new DateTime();
        $orderData->domainId = Domain::FIRST_DOMAIN_ID;
        $orderData->currency = $this->currencyFacade->findByCode($mallOrderDetail[Order::KEY_CURRENCY_ID]);
        $orderData->createdAsAdministrator = null;
        $orderData->createdAsAdministratorName = null;

        $orderData->transport = $this->getTransport($mallOrderDetail);
        $orderData->payment = $this->getMallPayment();

        return $orderData;
    }

    /**
     * @param mixed[] $mallOrderItem
     * @return \Shopsys\FrameworkBundle\Model\Order\Item\OrderItemData
     */
    private function createOrderItem(array $mallOrderItem): OrderItemData
    {
        $product = $this->productFacade->getById($mallOrderItem[Order::KEY_ITEM_ID]);

        /** @var \Shopsys\ShopBundle\Model\Order\Item\OrderItemData $orderItem */
        $orderItemData = $this->orderItemDataFactory->create();

        $orderItemData->name = $product->getName(self::CZECH_LOCALE);
        $orderItemPrice = $this->mallImportPriceCalculatorCalculation->calculatePrice(strval($mallOrderItem[Order::KEY_ITEM_VAT]), strval($mallOrderItem[Order::KEY_ITEM_PRICE]));
        $orderItemData->priceWithVat = $orderItemPrice->getPriceWithVat();
        $orderItemData->priceWithoutVat = $orderItemPrice->getPriceWithoutVat();

        $orderItemTotalPrice = $this->mallImportPriceCalculatorCalculation->calculatePrice(strval($mallOrderItem[Order::KEY_ITEM_VAT]), strval($mallOrderItem[Order::KEY_ITEM_PRICE]), $mallOrderItem[Order::KEY_ITEM_QUANTITY]);
        $orderItemData->totalPriceWithVat = $orderItemTotalPrice->getPriceWithVat();
        $orderItemData->totalPriceWithoutVat = $orderItemTotalPrice->getPriceWithoutVat();

        $orderItemData->vatPercent = $mallOrderItem[Order::KEY_ITEM_VAT];
        $orderItemData->quantity = $mallOrderItem[Order::KEY_ITEM_QUANTITY];
        $orderItemData->unitName = $product->getUnit()->getName(self::CZECH_LOCALE);
        $orderItemData->catnum = $product->getCatnum();

        $orderItemData->usePriceCalculation = true;

        return $orderItemData;
    }

    /**
     * @param \MPAPI\Entity\Order $mallOrderDetail
     * @throws \Shopsys\ShopBundle\Model\Order\Mall\Exception\NoTransportForMallIdException
     * @return \Shopsys\ShopBundle\Model\Transport\Transport
     */
    private function getTransport(Order $mallOrderDetail): Transport
    {
        $mallTransportName = $mallOrderDetail->getDeliveryMethod();
        $mallTransport = $this->transportFacade->getFirstTransportByMallTransportName($mallTransportName);

        if ($mallTransport === null) {
            throw new NoTransportForMallIdException($mallTransportName);
        }

        return $mallTransport;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Payment\Payment
     */
    private function getMallPayment(): Payment
    {
        $mallPayment = $this->paymentFacade->getFirstPaymentByType(Payment::TYPE_MALL);

        if ($mallPayment === null) {
            throw new NoMallPaymentTypeExistException();
        }

        return $mallPayment;
    }
}
