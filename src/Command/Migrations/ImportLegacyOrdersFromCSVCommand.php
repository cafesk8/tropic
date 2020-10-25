<?php

declare(strict_types=1);

namespace App\Command\Migrations;

use App\Component\Doctrine\SqlLoggerFacade;
use App\Component\Domain\DomainHelper;
use App\Model\Country\CountryFacade;
use App\Model\Customer\Migration\Issue\OrderMigrationIssue;
use App\Model\Customer\User\CustomerUserFacade;
use App\Model\Order\Item\OrderItemFactory;
use App\Model\Order\Migration\LegacyOrderValidator;
use App\Model\Order\Order;
use App\Model\Order\OrderData;
use App\Model\Order\OrderDataFactory;
use App\Model\Order\OrderFacade;
use App\Model\Order\Status\OrderStatus;
use App\Model\Order\Status\OrderStatusFacade;
use App\Model\Payment\Payment;
use App\Model\Payment\PaymentDataFactory;
use App\Model\Payment\PaymentFacade;
use App\Model\Pricing\Currency\CurrencyFacade;
use App\Model\Pricing\Vat\VatFacade;
use App\Model\Product\ProductFacade;
use App\Model\Transport\Transport;
use App\Model\Transport\TransportDataFactory;
use App\Model\Transport\TransportFacade;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemInterface;
use Shopsys\Cdn\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Order\OrderFactory;
use Shopsys\FrameworkBundle\Model\Order\OrderHashGeneratorRepository;
use Shopsys\FrameworkBundle\Model\Order\OrderPriceCalculation;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

ini_set('memory_limit','-1');

class ImportLegacyOrdersFromCSVCommand extends Command
{
    private const CSV_SKIP_N_ROWS = 1;
    private const CSV_DELIMITER = ';';

    private const ORDER_LEGACY_DOMAIN_ID_CZ = 1;

    private const ORDER_COL_INDEX_LEGACY_ID = 0;
    private const ORDER_COL_INDEX_STATUS = 1;
    private const ORDER_COL_INDEX_CUSTOMER_LEGACY_ID = 2;
    private const ORDER_COL_INDEX_CREATED_AT = 4;
    private const ORDER_COL_INDEX_HASH = 5;
    private const ORDER_COL_INDEX_NUMBER = 6;
    private const ORDER_COL_INDEX_DOMAIN_ID = 7;

    private const ORDER_COL_INDEX_DELIVERY_FIRST_NAME = 9;
    private const ORDER_COL_INDEX_DELIVERY_LAST_NAME = 10;
    private const ORDER_COL_INDEX_DELIVERY_COMPANY_NAME = 11;
    private const ORDER_COL_INDEX_DELIVERY_PHONE = 12;
    private const ORDER_COL_INDEX_DELIVERY_STREET = 13;
    private const ORDER_COL_INDEX_DELIVERY_CITY = 14;
    private const ORDER_COL_INDEX_DELIVERY_POSTCODE = 15;

    private const ORDER_COL_INDEX_BILLING_FIRST_NAME = 18;
    private const ORDER_COL_INDEX_BILLING_LAST_NAME = 19;
    private const ORDER_COL_INDEX_BILLING_EMAIL = 20;
    private const ORDER_COL_INDEX_BILLING_PHONE = 21;
    private const ORDER_COL_INDEX_BILLING_STREET = 22;
    private const ORDER_COL_INDEX_BILLING_CITY = 23;
    private const ORDER_COL_INDEX_BILLING_POSTCODE = 24;
    private const ORDER_COL_INDEX_BILLING_COMPANY_NAME = 25;
    private const ORDER_COL_INDEX_BILLING_COMPANY_NUMBER = 26;
    private const ORDER_COL_INDEX_BILLING_COMPANY_TAX_NUMBER = 27;
    private const ORDER_COL_INDEX_TRANSPORT_NAME = 30;
    private const ORDER_COL_INDEX_PAYMENT_NAME = 31;
    private const ORDER_COL_INDEX_DELIVERY_COUNTRY_CODE = 32;
    private const ORDER_COL_INDEX_BILLING_COUNTRY_CODE = 33;
    private const ORDER_COL_INDEX_PRICE_ROUNDING_WITHOUT_VAT = 35;
    private const ORDER_COL_INDEX_PRICE_ROUNDING_WITH_VAT = 36;
    private const ORDER_COL_INDEX_ROUNDED_PRICE_TOTAL_WITHOUT_VAT = 37;
    private const ORDER_COL_INDEX_ROUNDED_PRICE_TOTAL_WITH_VAT = 38;
    private const ORDER_COL_INDEX_ORDER_PRICE_WITH_VAT = 39;

    private const GOOD_COL_INDEX_LEGACY_ID = 0;
    private const GOOD_COL_INDEX_ORDER_LEGACY_ID = 1;
    private const GOOD_COL_INDEX_NAME = 3;
    private const GOOD_COL_INDEX_CATNUM = 4;
    private const GOOD_COL_INDEX_VARIANT = 5;
    private const GOOD_COL_INDEX_QUANTITY = 6;
    private const GOOD_COL_INDEX_PRICE_WITHOUT_VAT_OLD = 7;
    private const GOOD_COL_INDEX_VAT = 8;
    private const GOOD_COL_INDEX_TYPE = 13;
    private const GOOD_COL_INDEX_UNIT = 15;
    private const GOOD_COL_INDEX_PRICE_WITHOUT_VAT = 16;
    private const GOOD_COL_INDEX_PRICE_WITH_VAT = 17;

    private OutputInterface $output;

    private SqlLoggerFacade $sqlLoggerFacade;

    private EntityManagerInterface $entityManager;

    private array $skippedInvalidOrders = [];

    private OrderDataFactory $orderDataFactory;

    private OrderHashGeneratorRepository $orderHashGeneratorRepository;

    private CurrencyFacade $currencyFacade;

    private TransportFacade $transportFacade;

    private TransportDataFactory $transportDataFactory;

    private VatFacade $vatFacade;

    private PaymentFacade $paymentFacade;

    private PaymentDataFactory $paymentDataFactory;

    private CountryFacade $countryFacade;

    private OrderStatusFacade $orderStatusFacade;

    private Domain $domain;

    private CustomerUserFacade $customerUserFacade;

    private OrderFactory $orderFactory;

    private ProductFacade $productFacade;

    private OrderItemFactory $orderItemFactory;

    private OrderPriceCalculation $orderPriceCalculation;

    private OrderFacade $orderFacade;

    private LegacyOrderValidator $legacyOrderValidator;

    private FilesystemInterface $filesystem;

    /**
     * @param string $shopsysMigrationsDirPath
     * @param \App\Component\Doctrine\SqlLoggerFacade $sqlLoggerFacade
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \App\Model\Order\OrderDataFactory $orderDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderHashGeneratorRepository $orderHashGeneratorRepository
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \App\Model\Transport\TransportFacade $transportFacade
     * @param \App\Model\Transport\TransportDataFactory $transportDataFactory
     * @param \App\Model\Pricing\Vat\VatFacade $vatFacade
     * @param \App\Model\Payment\PaymentFacade $paymentFacade
     * @param \App\Model\Payment\PaymentDataFactory $paymentDataFactory
     * @param \App\Model\Country\CountryFacade $countryFacade
     * @param \App\Model\Order\Status\OrderStatusFacade $orderStatusFacade
     * @param \Shopsys\Cdn\Component\Domain\Domain $domain
     * @param \App\Model\Customer\User\CustomerUserFacade $customerUserFacade
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderFactory $orderFactory
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Order\Item\OrderItemFactory $orderItemFactory
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderPriceCalculation $orderPriceCalculation
     * @param \App\Model\Order\OrderFacade $orderFacade
     * @param \App\Model\Order\Migration\LegacyOrderValidator $legacyOrderValidator
     * @param \League\Flysystem\FilesystemInterface $filesystem
     */
    public function __construct(
        SqlLoggerFacade $sqlLoggerFacade,
        EntityManagerInterface $entityManager,
        OrderDataFactory $orderDataFactory,
        OrderHashGeneratorRepository $orderHashGeneratorRepository,
        CurrencyFacade $currencyFacade,
        TransportFacade $transportFacade,
        TransportDataFactory $transportDataFactory,
        VatFacade $vatFacade,
        PaymentFacade $paymentFacade,
        PaymentDataFactory $paymentDataFactory,
        CountryFacade $countryFacade,
        OrderStatusFacade $orderStatusFacade,
        Domain $domain,
        CustomerUserFacade $customerUserFacade,
        OrderFactory $orderFactory,
        ProductFacade $productFacade,
        OrderItemFactory $orderItemFactory,
        OrderPriceCalculation $orderPriceCalculation,
        OrderFacade $orderFacade,
        LegacyOrderValidator $legacyOrderValidator,
        FilesystemInterface $filesystem
    ) {
        parent::__construct();

        $this->sqlLoggerFacade = $sqlLoggerFacade;
        $this->entityManager = $entityManager;
        $this->orderDataFactory = $orderDataFactory;
        $this->orderHashGeneratorRepository = $orderHashGeneratorRepository;
        $this->currencyFacade = $currencyFacade;
        $this->transportFacade = $transportFacade;
        $this->transportDataFactory = $transportDataFactory;
        $this->vatFacade = $vatFacade;
        $this->paymentFacade = $paymentFacade;
        $this->paymentDataFactory = $paymentDataFactory;
        $this->countryFacade = $countryFacade;
        $this->orderStatusFacade = $orderStatusFacade;
        $this->domain = $domain;
        $this->customerUserFacade = $customerUserFacade;
        $this->orderFactory = $orderFactory;
        $this->productFacade = $productFacade;
        $this->orderItemFactory = $orderItemFactory;
        $this->orderPriceCalculation = $orderPriceCalculation;
        $this->orderFacade = $orderFacade;
        $this->legacyOrderValidator = $legacyOrderValidator;
        $this->filesystem = $filesystem;
    }

    protected function configure(): void
    {
        $this
            ->setName('shopsys:import:legacy-orders')
            ->setDescription('Migrate orders data from old shop');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->sqlLoggerFacade->temporarilyDisableLogging();

        $this->output->writeln('<info>Importing orders</info>');
        $this->importOrders();
        $this->output->writeln('<info>Finished.</info>');
        $this->displaySkippedOrders();

        $this->sqlLoggerFacade->reenableLogging();

        return 0;
    }

    private function importOrders(): void
    {
        $ordersCsvPath = '/web/content/uploadedFiles/migrations/orders.csv';
        $orderCsvString = $this->filesystem->read($ordersCsvPath);

        $ordersGoodsCsvPath = '/web/content/uploadedFiles/migrations/ordersgoods.csv';
        $ordersGoodsCsvString = $this->filesystem->read($ordersGoodsCsvPath);

        if ($orderCsvString === false) {
            throw new \IOException(sprintf("Can't read file: %s", $ordersCsvPath));
        }

        if ($ordersGoodsCsvString === false) {
            throw new \IOException(sprintf("Can't read file: %s", $ordersGoodsCsvPath));
        }

        $ordersCsvRows = $this->getRowsFromCsvString($orderCsvString);
        $ordersGoodsCsvRows = $this->getRowsFromCsvString($ordersGoodsCsvString);

        $pBar = $this->createProgressBar($this->output, count($ordersCsvRows) - self::CSV_SKIP_N_ROWS);
        $pBar->start();

        $countTotal = 0;
        $countCreated = 0;
        $countUpdated = 0;
        $countSkipped = 0;

        foreach ($ordersCsvRows as $orderCsvRow) {
            $countTotal++;
            $pBar->advance();

            if ($countTotal <= self::CSV_SKIP_N_ROWS) {
                $countSkipped++;
                continue;
            }

            try {
                $orderLegacyId = (int)$orderCsvRow[self::ORDER_COL_INDEX_LEGACY_ID];
                $orderGoodsCsvRows = $this->getOrderGoodsCsvRowsByOrderId($ordersGoodsCsvRows, $orderLegacyId);

                if (count($orderGoodsCsvRows) < 1) {
                    $countSkipped++;
                    continue;
                }

                $order = $this->orderFacade->findByLegacyId($orderLegacyId);
                if ($order !== null) {
                    $countSkipped++;
                    continue;
                }

                $this->entityManager->beginTransaction();
                $order = $this->createOrderFromCsvRow($orderCsvRow, $orderGoodsCsvRows);

                $this->legacyOrderValidator->validate(
                    $order,
                    Money::create($orderCsvRow[self::ORDER_COL_INDEX_ROUNDED_PRICE_TOTAL_WITH_VAT]),
                    Money::create($orderCsvRow[self::ORDER_COL_INDEX_ROUNDED_PRICE_TOTAL_WITHOUT_VAT]),
                    Money::create($orderCsvRow[self::ORDER_COL_INDEX_ORDER_PRICE_WITH_VAT])
                );

                $this->entityManager->persist($order);
                $this->entityManager->flush();
                $this->entityManager->commit();

                $countCreated++;
            } catch (\Exception $exc) {
                $this->setInvalidOrder($orderCsvRow, $exc->getMessage());
                $countSkipped++;

                $this->entityManager->rollback();
            } finally {
                $this->entityManager->clear();
            }
        }

        $this->output->writeln(PHP_EOL . sprintf(
            'Created: %d, Updated: %d, Skipped: %d, Total: %d',
            $countCreated,
            $countUpdated,
            $countSkipped,
            $countTotal
        ));
        $pBar->finish();
    }

    /**
     * @param string $csvString
     * @return array
     */
    private function getRowsFromCsvString(string $csvString): array
    {
        $rows = explode("\n", $csvString);
        $csvArray = [];
        foreach($rows as $row){
            $parsedRow = str_getcsv($row, self::CSV_DELIMITER, '"');
            if (count($parsedRow) > 1) {
                $csvArray[] = $parsedRow;
            }
        }

        return $csvArray;
    }

    /**
     * @param array $orderCsvRow
     * @param array $orderGoodsCsvRows
     * @return \App\Model\Order\Order
     */
    private function createOrderFromCsvRow(array $orderCsvRow, array $orderGoodsCsvRows): Order
    {
        $orderData = $this->orderDataFactory->create();
        $this->mapOrderCsvRowToProductData($orderCsvRow, $orderData);

        $hash = !empty($orderCsvRow[self::ORDER_COL_INDEX_HASH]) ? $orderCsvRow[self::ORDER_COL_INDEX_HASH] : $this->orderHashGeneratorRepository->getUniqueHash();
        $user = $this->customerUserFacade->findByLegacyId((int)$orderCsvRow[self::ORDER_COL_INDEX_CUSTOMER_LEGACY_ID]);

        /** @var \App\Model\Order\Order $order */
        $order = $this->orderFactory->create(
            $orderData,
            $orderCsvRow[self::ORDER_COL_INDEX_NUMBER],
            $hash,
            $user
        );

        $this->mapOrderGoodsCsvRowsToOrderItems($orderGoodsCsvRows, $orderCsvRow, $order);

        return $order;
    }

    /**
     * @param array $orderCsvRow
     * @param \App\Model\Order\OrderData $orderData
     */
    private function mapOrderCsvRowToProductData(array $orderCsvRow, OrderData $orderData): void
    {
        $legacyDomainId = (int)$orderCsvRow[self::ORDER_COL_INDEX_DOMAIN_ID];
        $locale = $this->getLocaleByLegacyDomainId($legacyDomainId);

        $orderData->legacyId = (int)$orderCsvRow[self::ORDER_COL_INDEX_LEGACY_ID];
        $orderData->domainId = $legacyDomainId;
        $orderData->currency = $this->currencyFacade->getDomainDefaultCurrencyByDomainId($orderData->domainId);
        $orderData->email = $this->getMaxSizeValue(trim($orderCsvRow[self::ORDER_COL_INDEX_BILLING_EMAIL]), 50);
        $orderData->createdAt = new \DateTime();
        $orderData->createdAt->setTimestamp((int)$orderCsvRow[self::ORDER_COL_INDEX_CREATED_AT]);

        $orderData->exportedAt = new \DateTime();
        $orderData->exportedAt->setTimestamp((int)$orderCsvRow[self::ORDER_COL_INDEX_CREATED_AT]);
        $orderData->exportStatus = Order::EXPORT_SUCCESS;

        $orderData->transport = $this->findOrCreateTransportByName($orderCsvRow[self::ORDER_COL_INDEX_TRANSPORT_NAME], $locale);
        $orderData->payment = $this->findOrCreatePayment($orderCsvRow, $locale, $orderData->domainId);
        $orderData->status = $this->getOrderStatusByLegacyStatusId((int)$orderCsvRow[self::ORDER_COL_INDEX_STATUS]);

        if (!empty($orderCsvRow[self::ORDER_COL_INDEX_BILLING_COMPANY_NAME])) {
            $orderData->companyName = $this->getMaxSizeValue($orderCsvRow[self::ORDER_COL_INDEX_BILLING_COMPANY_NAME], 100);
        }
        if (!empty($orderCsvRow[self::ORDER_COL_INDEX_BILLING_COMPANY_NUMBER])) {
            $orderData->companyNumber = $this->getMaxSizeValue(trim($orderCsvRow[self::ORDER_COL_INDEX_BILLING_COMPANY_NUMBER]), 20);
        }
        if (!empty($orderCsvRow[self::ORDER_COL_INDEX_BILLING_COMPANY_TAX_NUMBER])) {
            $orderData->companyTaxNumber = $this->getMaxSizeValue(trim($orderCsvRow[self::ORDER_COL_INDEX_BILLING_COMPANY_TAX_NUMBER]), 30);
        }

        $orderData->country = $this->countryFacade->getByCode($orderCsvRow[self::ORDER_COL_INDEX_BILLING_COUNTRY_CODE]);
        $orderData->city = $this->getMaxSizeValue($orderCsvRow[self::ORDER_COL_INDEX_BILLING_CITY], 100);
        $orderData->postcode = $this->getMaxSizeValue(trim($orderCsvRow[self::ORDER_COL_INDEX_BILLING_POSTCODE]), 6);

        $orderData->firstName = $this->getMaxSizeValue(trim($orderCsvRow[self::ORDER_COL_INDEX_BILLING_FIRST_NAME]), LegacyOrderValidator::FIRST_NAME_LENGTH);
        $orderData->lastName = $this->getMaxSizeValue(trim($orderCsvRow[self::ORDER_COL_INDEX_BILLING_LAST_NAME]), LegacyOrderValidator::LAST_NAME_LENGTH);
        $orderData->street = $this->getMaxSizeValue($orderCsvRow[self::ORDER_COL_INDEX_BILLING_STREET], 100);
        $orderData->telephone = $this->getMaxSizeValue(trim($orderCsvRow[self::ORDER_COL_INDEX_BILLING_PHONE]), 20);

        if (empty($orderCsvRow[self::ORDER_COL_INDEX_DELIVERY_STREET])) {
            $orderData->deliveryAddressSameAsBillingAddress = true;

            $orderData->deliveryCountry = $this->countryFacade->getByCode($orderCsvRow[self::ORDER_COL_INDEX_BILLING_COUNTRY_CODE]);
            $orderData->deliveryCity = $this->getMaxSizeValue($orderCsvRow[self::ORDER_COL_INDEX_BILLING_CITY], 100);
            $orderData->deliveryPostcode = $this->getMaxSizeValue(trim($orderCsvRow[self::ORDER_COL_INDEX_BILLING_POSTCODE]), 6);
            if (!empty($orderCsvRow[self::ORDER_COL_INDEX_BILLING_COMPANY_NAME])) {
                $orderData->deliveryCompanyName = $this->getMaxSizeValue($orderCsvRow[self::ORDER_COL_INDEX_BILLING_COMPANY_NAME], 100);
            }

            $orderData->deliveryFirstName = $this->getMaxSizeValue(trim($orderCsvRow[self::ORDER_COL_INDEX_BILLING_FIRST_NAME]), LegacyOrderValidator::FIRST_NAME_LENGTH);
            $orderData->deliveryLastName = $this->getMaxSizeValue(trim($orderCsvRow[self::ORDER_COL_INDEX_BILLING_LAST_NAME]), LegacyOrderValidator::LAST_NAME_LENGTH);

            $orderData->deliveryStreet = $this->getMaxSizeValue($orderCsvRow[self::ORDER_COL_INDEX_BILLING_STREET], 100);
            $orderData->deliveryTelephone = $this->getMaxSizeValue(trim($orderCsvRow[self::ORDER_COL_INDEX_BILLING_PHONE]), 30);
        } else {
            $orderData->deliveryAddressSameAsBillingAddress = false;

            $orderData->deliveryCountry = $this->countryFacade->getByCode($orderCsvRow[self::ORDER_COL_INDEX_DELIVERY_COUNTRY_CODE]);
            $orderData->deliveryCity = $this->getMaxSizeValue($orderCsvRow[self::ORDER_COL_INDEX_DELIVERY_CITY], 100);
            $orderData->deliveryPostcode = $this->getMaxSizeValue(trim($orderCsvRow[self::ORDER_COL_INDEX_DELIVERY_POSTCODE]), 6);
            if (!empty($orderCsvRow[self::ORDER_COL_INDEX_DELIVERY_COMPANY_NAME])) {
                $orderData->deliveryCompanyName = $this->getMaxSizeValue($orderCsvRow[self::ORDER_COL_INDEX_DELIVERY_COMPANY_NAME], 100);
            }

            $orderData->deliveryFirstName = $this->getMaxSizeValue(trim($orderCsvRow[self::ORDER_COL_INDEX_DELIVERY_FIRST_NAME]), LegacyOrderValidator::FIRST_NAME_LENGTH);
            $orderData->deliveryLastName = $this->getMaxSizeValue(trim($orderCsvRow[self::ORDER_COL_INDEX_DELIVERY_LAST_NAME]), LegacyOrderValidator::LAST_NAME_LENGTH);
            $orderData->deliveryStreet = $this->getMaxSizeValue($orderCsvRow[self::ORDER_COL_INDEX_DELIVERY_STREET], 100);
            $orderData->deliveryTelephone = $this->getMaxSizeValue(trim($orderCsvRow[self::ORDER_COL_INDEX_DELIVERY_PHONE]), 30);
        }
    }

    /**
     * @param array $orderGoodsCsvRow
     * @param array $orderCsvRow
     * @param \App\Model\Order\Order $order
     */
    private function mapOrderGoodsCsvRowsToOrderItems(array $orderGoodsCsvRow, array $orderCsvRow, Order $order): void
    {
        $orderLocale = $this->getLocaleByLegacyDomainId($order->getDomainId());

        foreach ($orderGoodsCsvRow as $orderGoodCsvRow) {
            $orderItemPriceWithoutVat = $this->getOrderItemPriceWithoutVat($orderGoodCsvRow);
            $orderItemPriceWithVat = $this->getOrderItemPriceWithVat($orderGoodCsvRow);

            switch ($orderGoodCsvRow[self::GOOD_COL_INDEX_TYPE]) {
                case 'good':
                case 'recyklace':
                case 'sleva':
                case '':
                    $product = $this->productFacade->getByCatnum($orderGoodCsvRow[self::GOOD_COL_INDEX_CATNUM]);

                    if (is_array($product) && !empty($product)) {
                        $product = reset($product);
                    } elseif (empty($product)) {
                        $product = null;
                    }

                    $orderItemName = $orderGoodCsvRow[self::GOOD_COL_INDEX_NAME];
                    if (!empty($orderGoodCsvRow[self::GOOD_COL_INDEX_VARIANT])) {
                        $orderItemName .= ' ' . $orderGoodCsvRow[self::GOOD_COL_INDEX_VARIANT];
                    }

                    $orderItem = $this->orderItemFactory->createProduct(
                        $order,
                        $orderItemName,
                        new Price(
                            Money::create($orderItemPriceWithoutVat),
                            Money::create($orderItemPriceWithVat)
                        ),
                        $orderGoodCsvRow[self::GOOD_COL_INDEX_VAT],
                        (int)$orderGoodCsvRow[self::GOOD_COL_INDEX_QUANTITY],
                        $product !== null ? $product->getUnit()->getName($orderLocale) : $orderGoodCsvRow[self::GOOD_COL_INDEX_UNIT],
                        $orderGoodCsvRow[self::GOOD_COL_INDEX_CATNUM],
                        $product
                    );
                    break;
                case 'expedice':
                    $orderItem = $this->orderItemFactory->createTransport(
                        $order,
                        $orderGoodCsvRow[self::GOOD_COL_INDEX_NAME],
                        new Price(
                            Money::create($orderItemPriceWithoutVat),
                            Money::create($orderItemPriceWithVat)
                        ),
                        $orderGoodCsvRow[self::GOOD_COL_INDEX_VAT],
                        (int)$orderGoodCsvRow[self::GOOD_COL_INDEX_QUANTITY],
                        $order->getTransport()
                    );
                    break;
                case 'payment':
                    $orderItem = $this->orderItemFactory->createPayment(
                        $order,
                        $orderGoodCsvRow[self::GOOD_COL_INDEX_NAME],
                        new Price(
                            Money::create($orderItemPriceWithoutVat),
                            Money::create($orderItemPriceWithVat)
                        ),
                        $orderGoodCsvRow[self::GOOD_COL_INDEX_VAT],
                        (int)$orderGoodCsvRow[self::GOOD_COL_INDEX_QUANTITY],
                        $order->getPayment()
                    );
                    break;
                default:
                    throw new \InvalidArgumentException(
                        sprintf('Unsupported order item type "%s" of item ID %d', $orderGoodCsvRow[self::GOOD_COL_INDEX_TYPE], $orderGoodCsvRow[self::GOOD_COL_INDEX_LEGACY_ID])
                    );
            }

            $orderItemQuantity = (int)$orderGoodCsvRow[self::GOOD_COL_INDEX_QUANTITY];
            $orderItem->setTotalPrice(new Price(
                Money::create((string)($orderItemPriceWithoutVat * $orderItemQuantity)), // @phpstan-ignore-line
                Money::create((string)($orderItemPriceWithVat * $orderItemQuantity)) // @phpstan-ignore-line
            ));

            $order->addItem($orderItem);
            $this->entityManager->persist($orderItem);
        }

        $this->addRoundingItemToOrder($order, $orderLocale, $orderCsvRow);

        $order->setTotalPrice(
            $this->orderPriceCalculation->getOrderTotalPrice($order)
        );
    }

    /**
     * @param array $orderGoodRow
     * @return string
     */
    private function getOrderItemPriceWithoutVat(array $orderGoodRow): string
    {
        return (float)$orderGoodRow[self::GOOD_COL_INDEX_PRICE_WITHOUT_VAT] === 0.0
            ? $orderGoodRow[self::GOOD_COL_INDEX_PRICE_WITHOUT_VAT_OLD] : $orderGoodRow[self::GOOD_COL_INDEX_PRICE_WITHOUT_VAT];
    }

    /**
     * @param array $orderGoodCsvRow
     * @return string
     */
    private function getOrderItemPriceWithVat(array $orderGoodCsvRow): string
    {
        if ((float)$orderGoodCsvRow[self::GOOD_COL_INDEX_PRICE_WITH_VAT] === 0.0) {
            return (string)round($orderGoodCsvRow[self::GOOD_COL_INDEX_PRICE_WITHOUT_VAT_OLD] / 100 * (100 + $orderGoodCsvRow[self::GOOD_COL_INDEX_VAT]), 2);
        }

        return $orderGoodCsvRow[self::GOOD_COL_INDEX_PRICE_WITH_VAT];
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param string $locale
     * @param array $orderCsvRow
     */
    private function addRoundingItemToOrder(Order $order, string $locale, array $orderCsvRow): void
    {
        $orderTotalPrice = $this->orderPriceCalculation->getOrderTotalPrice($order);
        $legacyRoundedTotalPriceWithoutVat = Money::create($orderCsvRow[self::ORDER_COL_INDEX_ROUNDED_PRICE_TOTAL_WITHOUT_VAT]);
        $legacyRoundedTotalPriceWithVat = Money::create($orderCsvRow[self::ORDER_COL_INDEX_ROUNDED_PRICE_TOTAL_WITH_VAT]);
        $roundingPrice = null;
        if (!$legacyRoundedTotalPriceWithoutVat->isZero() || !$legacyRoundedTotalPriceWithVat->isZero()) {
            $priceRoundingWithoutVat = Money::create($orderCsvRow[self::ORDER_COL_INDEX_PRICE_ROUNDING_WITHOUT_VAT]);
            $priceRoundingWithVat = Money::create($orderCsvRow[self::ORDER_COL_INDEX_PRICE_ROUNDING_WITH_VAT]);

            $roundedTotalPriceWithoutVat = $orderTotalPrice->getPriceWithoutVat()->add($priceRoundingWithoutVat);
            $roundedTotalPriceWithVat = $orderTotalPrice->getPriceWithVat()->add($priceRoundingWithVat);

            $priceDifferenceWithoutVat = $roundedTotalPriceWithoutVat->subtract($legacyRoundedTotalPriceWithoutVat);
            $priceDifferenceWithVat = $roundedTotalPriceWithVat->subtract($legacyRoundedTotalPriceWithVat);

            $priceRoundingWithoutVat = $priceRoundingWithoutVat->add($priceDifferenceWithoutVat->multiply(-1));
            $priceRoundingWithVat = $priceRoundingWithVat->add($priceDifferenceWithVat->multiply(-1));
            $roundingPrice = new Price($priceRoundingWithoutVat, $priceRoundingWithVat);
        } else {
            $legacyOrderPriceWithVat = Money::create($orderCsvRow[self::ORDER_COL_INDEX_ORDER_PRICE_WITH_VAT]);
            $priceDifferenceWithVat = $orderTotalPrice->getPriceWithVat()->subtract($legacyOrderPriceWithVat);
            $vat = $order->getDomainId() === DomainHelper::CZECH_DOMAIN ? '1.21' : '1.20';
            $priceDifferenceWithoutVat = $priceDifferenceWithVat->divide($vat, 2);
            $roundingPrice = new Price($priceDifferenceWithoutVat, $priceDifferenceWithVat->multiply(-1));
        }

        if ($roundingPrice !== null && (!$roundingPrice->getPriceWithVat()->isZero() || !$roundingPrice->getPriceWithoutVat()->isZero())) {
            $orderItem = $this->orderItemFactory->createProduct(
                $order,
                t('Rounding', [], 'messages', $locale),
                $roundingPrice,
                '0',
                1,
                null,
                null,
                null
            );
            $orderItem->setTotalPrice($roundingPrice);

            $this->entityManager->persist($orderItem);
        }
    }

    /**
     * @param string $transportName
     * @param string $orderLocale
     * @return \App\Model\Transport\Transport
     */
    private function findOrCreateTransportByName(string $transportName, string $orderLocale): Transport
    {
        $transport = $this->transportFacade->findByName($transportName, $orderLocale);

        if ($transport === null) {
            $transportData = $this->transportDataFactory->create();

            foreach ($this->domain->getAllLocales() as $locale) {
                $transportData->name[$locale] = $transportName;
            }

            foreach ($this->domain->getAllIds() as $domainId) {
                $transportData->vatsIndexedByDomainId[$domainId] = $this->vatFacade->getDefaultVatForDomain($domainId);
                $transportData->pricesIndexedByDomainId[$domainId] = Money::zero();
            }

            $transport = $this->transportFacade->create($transportData);
            $this->transportFacade->deleteById($transport->getId());
        }

        return $transport;
    }

    /**
     * @param array $orderCsvRow
     * @param string $orderLocale
     * @param int $orderDomainId
     * @return \App\Model\Payment\Payment
     */
    private function findOrCreatePayment(array $orderCsvRow, string $orderLocale, int $orderDomainId): Payment
    {
        $paymentName = $orderCsvRow[self::ORDER_COL_INDEX_PAYMENT_NAME] === 'NULL' ? 'Neznámý název' : $orderCsvRow[self::ORDER_COL_INDEX_PAYMENT_NAME];
        $isCzkRounding = $orderDomainId === DomainHelper::CZECH_DOMAIN;

        $payment = $this->paymentFacade->findByNameAndCzkRounding($paymentName, $isCzkRounding, $orderLocale);
        if ($payment === null) {
            $paymentData = $this->paymentDataFactory->create();
            $paymentData->czkRounding = $isCzkRounding;
            foreach ($this->domain->getAllLocales() as $locale) {
                $paymentData->name[$locale] = $paymentName;
            }

            foreach ($this->domain->getAllIds() as $domainId) {
                $paymentData->vatsIndexedByDomainId[$domainId] = $this->vatFacade->getDefaultVatForDomain($domainId);
                $paymentData->pricesIndexedByDomainId[$domainId] = Money::zero();
            }

            $payment = $this->paymentFacade->create($paymentData);
            $this->paymentFacade->deleteById($payment->getId());
        }

        return $payment;
    }

    /**
     * @param int $legacyStatusId
     * @return \App\Model\Order\Status\OrderStatus
     */
    private function getOrderStatusByLegacyStatusId(int $legacyStatusId): OrderStatus
    {
        $newStatusIdIndexedByLegacyId = [
            1 => OrderStatus::TYPE_NEW,
            2 => OrderStatus::TYPE_DONE,
            3 => OrderStatus::TYPE_CANCELED,
            4 => OrderStatus::TYPE_IN_PROGRESS,
            5 => OrderStatus::TYPE_CUSTOMER_DID_NOT_PICK_UP,
            6 => OrderStatus::TYPE_PAID,
        ];

        return $this->orderStatusFacade->getByType(
            $newStatusIdIndexedByLegacyId[$legacyStatusId]
        );
    }

    /**
     * @param array $csvRow
     * @param string $skipReason
     */
    private function setInvalidOrder(array $csvRow, string $skipReason): void
    {
        $this->skippedInvalidOrders[] = [
            'legacyId' => (int)$csvRow[self::ORDER_COL_INDEX_LEGACY_ID],
            'legacyNumber' => $csvRow[self::ORDER_COL_INDEX_NUMBER],
            'skipReason' => $skipReason,
        ];
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param int $max
     * @return \Symfony\Component\Console\Helper\ProgressBar
     */
    private function createProgressBar(OutputInterface $output, int $max): ProgressBar
    {
        $progressBar = new ProgressBar($output, $max);
        $progressBar->setBarCharacter('<fg=magenta>=</>');
        $progressBar->setRedrawFrequency(100);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% ');

        return $progressBar;
    }

    /**
     * @param array $orderItemsCsvRows
     * @param int $orderLegacyId
     * @return array
     */
    private function getOrderGoodsCsvRowsByOrderId(array $orderItemsCsvRows, int $orderLegacyId): array
    {
        $orderGoodsCsvRows = [];

        foreach ($orderItemsCsvRows as $orderItemsCsvRow) {
            if ($orderItemsCsvRow[self::GOOD_COL_INDEX_LEGACY_ID] === 'id') {
                continue;
            }
            if ((int)$orderItemsCsvRow[self::GOOD_COL_INDEX_ORDER_LEGACY_ID] === $orderLegacyId) {
                $orderGoodsCsvRows[] = $orderItemsCsvRow;
            }
        }

        return $orderGoodsCsvRows;
    }

    /**
     * @param int $legacyDomainId
     * @return string
     */
    private function getLocaleByLegacyDomainId(int $legacyDomainId): string
    {
        return $legacyDomainId === self::ORDER_LEGACY_DOMAIN_ID_CZ ? DomainHelper::CZECH_LOCALE : DomainHelper::SLOVAK_LOCALE;
    }

    private function displaySkippedOrders(): void
    {
        foreach ($this->skippedInvalidOrders as $invalidOrder) {
            $this->output->writeln(
                sprintf(
                    'Skipped order with legacyId: %d, legacyNumber: %s, reason: %s',
                    $invalidOrder['legacyId'],
                    $invalidOrder['legacyNumber'],
                    $invalidOrder['skipReason']
                )
            );

            $orderMigrationIssue = new OrderMigrationIssue();
            $orderMigrationIssue->setOrderLegacyId($invalidOrder['legacyId']);
            $orderMigrationIssue->setOrderLegacyNumber($invalidOrder['legacyNumber']);
            $orderMigrationIssue->setMessage($invalidOrder['skipReason']);

            $this->entityManager->persist($orderMigrationIssue);
        }
        $this->entityManager->flush();
    }

    /**
     * @param string $value
     * @return string
     */
    private function getValueOrDash(string $value): string
    {
        return empty($value) ? '-' : $value;
    }

    /**
     * @param string $value
     * @param int $maxSize
     * @return string
     */
    private function getMaxSizeValue(string $value, int $maxSize): string
    {
        return mb_substr($this->getValueOrDash($value), 0, $maxSize, 'UTF-8');
    }
}
