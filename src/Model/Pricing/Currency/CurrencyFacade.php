<?php

declare(strict_types=1);

namespace App\Model\Pricing\Currency;

use App\Component\Domain\DomainHelper;
use App\Model\Order\OrderRepository;
use App\Model\Payment\PaymentRepository;
use App\Model\Pricing\Group\PricingGroupFacade;
use App\Model\Pricing\Vat\VatFacade;
use App\Model\Product\ProductRepository;
use App\Model\Product\Transfer\ProductInfoQueueImportFacade;
use App\Model\Transport\TransportRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Payment\PaymentPriceFactoryInterface;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyData;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade as BaseCurrencyFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFactoryInterface;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyRepository;
use Shopsys\FrameworkBundle\Model\Pricing\PricingSetting;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculationScheduler;
use Shopsys\FrameworkBundle\Model\Transport\TransportPriceFactoryInterface;

/**
 * @property \App\Model\Order\OrderRepository $orderRepository
 * @property \App\Model\Payment\PaymentRepository $paymentRepository
 * @property \App\Model\Transport\TransportRepository $transportRepository
 * @method \App\Model\Pricing\Currency\Currency getById(int $currencyId)
 * @method \App\Model\Pricing\Currency\Currency create(\Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyData $currencyData)
 * @method \App\Model\Pricing\Currency\Currency[] getAll()
 * @method \App\Model\Pricing\Currency\Currency getDefaultCurrency()
 * @method \App\Model\Pricing\Currency\Currency getDomainDefaultCurrencyByDomainId(int $domainId)
 * @method setDefaultCurrency(\App\Model\Pricing\Currency\Currency $currency)
 * @method setDomainDefaultCurrency(\App\Model\Pricing\Currency\Currency $currency, int $domainId)
 * @method bool isDefaultCurrency(\App\Model\Pricing\Currency\Currency $currency)
 * @method \App\Model\Pricing\Currency\Currency[] getCurrenciesUsedInOrders()
 * @method \App\Model\Pricing\Currency\Currency[] getAllIndexedById()
 * @property \App\Model\Pricing\Vat\VatFacade $vatFacade
 */
class CurrencyFacade extends BaseCurrencyFacade
{
    private ProductRepository $productRepository;

    private ProductInfoQueueImportFacade $productInfoQueueImportFacade;

    private PricingGroupFacade $pricingGroupFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyRepository $currencyRepository
     * @param \Shopsys\FrameworkBundle\Model\Pricing\PricingSetting $pricingSetting
     * @param \App\Model\Order\OrderRepository $orderRepository
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculationScheduler $productPriceRecalculationScheduler
     * @param \App\Model\Payment\PaymentRepository $paymentRepository
     * @param \App\Model\Transport\TransportRepository $transportRepository
     * @param \Shopsys\FrameworkBundle\Model\Payment\PaymentPriceFactoryInterface $paymentPriceFactory
     * @param \Shopsys\FrameworkBundle\Model\Transport\TransportPriceFactoryInterface $transportPriceFactory
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFactoryInterface $currencyFactory
     * @param \App\Model\Pricing\Vat\VatFacade $vatFacade
     * @param \App\Model\Product\ProductRepository $productRepository
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \App\Model\Product\Transfer\ProductInfoQueueImportFacade $productInfoQueueImportFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        CurrencyRepository $currencyRepository,
        PricingSetting $pricingSetting,
        OrderRepository $orderRepository,
        Domain $domain,
        ProductPriceRecalculationScheduler $productPriceRecalculationScheduler,
        PaymentRepository $paymentRepository,
        TransportRepository $transportRepository,
        PaymentPriceFactoryInterface $paymentPriceFactory,
        TransportPriceFactoryInterface $transportPriceFactory,
        CurrencyFactoryInterface $currencyFactory,
        VatFacade $vatFacade,
        ProductRepository $productRepository,
        PricingGroupFacade $pricingGroupFacade,
        ProductInfoQueueImportFacade $productInfoQueueImportFacade
    ) {
        parent::__construct(
            $em,
            $currencyRepository,
            $pricingSetting,
            $orderRepository,
            $domain,
            $productPriceRecalculationScheduler,
            $paymentRepository,
            $transportRepository,
            $paymentPriceFactory,
            $transportPriceFactory,
            $currencyFactory,
            $vatFacade
        );
        $this->productRepository = $productRepository;
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->productInfoQueueImportFacade = $productInfoQueueImportFacade;
    }

    /**
     * @param string $code
     * @return \App\Model\Pricing\Currency\Currency|null
     */
    public function findByCode(string $code): ?Currency
    {
        /** @var \App\Model\Pricing\Currency\Currency|null $currency */
        $currency = $this->currencyRepository->findByCode($code);

        return $currency;
    }

    /**
     * @param int $currencyId
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyData $currencyData
     * @return \App\Model\Pricing\Currency\Currency
     */
    public function edit($currencyId, CurrencyData $currencyData): Currency
    {
        /** @var \App\Model\Pricing\Currency\Currency $currency */
        $currency = $this->currencyRepository->getById($currencyId);
        $currency->edit($currencyData);
        if ($this->isDefaultCurrency($currency)) {
            $currency->setExchangeRate(Currency::DEFAULT_EXCHANGE_RATE);
        } else {
            $currency->setExchangeRate($currencyData->exchangeRate);
        }
        $this->em->flush();
        $this->queueProductsWithSalePriceForImport();

        return $currency;
    }

    public function queueProductsWithSalePriceForImport(): void
    {
        $pohodaIds = [];
        $products = $this->productRepository->getProductsWithSalePrice(
            $this->pricingGroupFacade->getSalePricePricingGroup(DomainHelper::CZECH_DOMAIN)
        );

        foreach ($products as $product) {
            $pohodaId = $product->getPohodaId();

            if ($pohodaId !== null) {
                $pohodaIds[] = $pohodaId;
            }
        }

        $this->productInfoQueueImportFacade->insertChangedPohodaProductIds($pohodaIds, new DateTime());
    }
}
