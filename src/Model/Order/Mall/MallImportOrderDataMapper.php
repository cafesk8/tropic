<?php

declare(strict_types=1);

namespace App\Model\Order\Mall;

use App\Component\Domain\DomainHelper;
use App\Model\Country\CountryFacade;
use App\Model\Order\Mall\Exception\NoMallPaymentTypeExistException;
use App\Model\Order\Mall\Exception\NoTransportForMallIdException;
use App\Model\Order\OrderData;
use App\Model\Payment\Payment;
use App\Model\Payment\PaymentFacade;
use App\Model\Pricing\Currency\CurrencyFacade;
use App\Model\Product\ProductFacade;
use App\Model\Transport\Transport;
use App\Model\Transport\TransportFacade;
use DateTime;
use MPAPI\Entity\Order;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItemData;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItemDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Order\OrderDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Order\Status\OrderStatus;
use Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusFacade;

class MallImportOrderDataMapper
{
    private const CZECH_LOCALE = DomainHelper::CZECH_LOCALE;

    /**
     * @var \App\Model\Order\OrderDataFactory
     */
    private $orderDataFactory;

    /**
     * @var \App\Model\Order\Item\OrderItemDataFactory
     */
    private $orderItemDataFactory;

    /**
     * @var \App\Model\Country\CountryFacade
     */
    private $countryFacade;

    /**
     * @var \App\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \App\Model\Payment\PaymentFacade
     */
    private $paymentFacade;

    /**
     * @var \App\Model\Transport\TransportFacade
     */
    private $transportFacade;

    /**
     * @var \App\Model\Order\Status\OrderStatusFacade
     */
    private $orderStatusFacade;

    /**
     * @var \App\Model\Pricing\Currency\CurrencyFacade
     */
    private $currencyFacade;

    /**
     * @var \App\Model\Order\Mall\MallImportPriceCalculatorCalculation
     */
    private $mallImportPriceCalculatorCalculation;

    /**
     * @param \App\Model\Order\OrderDataFactory $orderDataFactory
     * @param \App\Model\Order\Item\OrderItemDataFactory $orderItemDataFactoryInterface
     * @param \App\Model\Country\CountryFacade $countryFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Payment\PaymentFacade $paymentFacade
     * @param \App\Model\Transport\TransportFacade $transportFacade
     * @param \App\Model\Order\Status\OrderStatusFacade $orderStatusFacade
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \App\Model\Order\Mall\MallImportPriceCalculatorCalculation $mallImportPriceCalculatorCalculation
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
     * @return \App\Model\Order\OrderData
     */
    public function createOrderDataFromMallOrderDetail(Order $mallOrderDetail): OrderData
    {
        /** @var \App\Model\Order\OrderData $orderData */
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
        $orderData->companyName = trim($mallOrderDetail->getCompany()) !== '' ? $mallOrderDetail->getCompany() : null;
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
        $orderData->currency = $this->currencyFacade->findByCode($mallOrderDetail->getCurrencyId());
        $orderData->createdAsAdministrator = null;
        $orderData->createdAsAdministratorName = null;

        $orderData->transport = $this->getTransport($mallOrderDetail);
        $orderData->payment = $this->getMallPayment();

        return $orderData;
    }

    /**
     * @param mixed[] $mallOrderItem
     * @return \App\Model\Order\Item\OrderItemData
     */
    private function createOrderItem(array $mallOrderItem): OrderItemData
    {
        $product = $this->productFacade->getById($mallOrderItem[Order::KEY_ID]);

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
     * @throws \App\Model\Order\Mall\Exception\NoTransportForMallIdException
     * @return \App\Model\Transport\Transport
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
     * @return \App\Model\Payment\Payment
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
