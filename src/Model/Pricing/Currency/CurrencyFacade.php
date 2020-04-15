<?php

declare(strict_types=1);

namespace App\Model\Pricing\Currency;

use Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade as BaseCurrencyFacade;

/**
 * @property \App\Model\Order\OrderRepository $orderRepository
 * @property \App\Model\Payment\PaymentRepository $paymentRepository
 * @property \App\Model\Transport\TransportRepository $transportRepository
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $em, \Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyRepository $currencyRepository, \Shopsys\FrameworkBundle\Model\Pricing\PricingSetting $pricingSetting, \App\Model\Order\OrderRepository $orderRepository, \Shopsys\FrameworkBundle\Component\Domain\Domain $domain, \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculationScheduler $productPriceRecalculationScheduler, \App\Model\Payment\PaymentRepository $paymentRepository, \App\Model\Transport\TransportRepository $transportRepository, \Shopsys\FrameworkBundle\Model\Payment\PaymentPriceFactoryInterface $paymentPriceFactory, \Shopsys\FrameworkBundle\Model\Transport\TransportPriceFactoryInterface $transportPriceFactory, \Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFactoryInterface $currencyFactory, \App\Model\Pricing\Vat\VatFacade $vatFacade)
 * @method \App\Model\Pricing\Currency\Currency getById(int $currencyId)
 * @method \App\Model\Pricing\Currency\Currency create(\Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyData $currencyData)
 * @method \App\Model\Pricing\Currency\Currency edit(int $currencyId, \Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyData $currencyData)
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
}
