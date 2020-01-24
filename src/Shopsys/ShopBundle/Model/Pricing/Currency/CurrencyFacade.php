<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Pricing\Currency;

use Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade as BaseCurrencyFacade;

/**
 * @property \Shopsys\ShopBundle\Model\Order\OrderRepository $orderRepository
 * @property \Shopsys\ShopBundle\Model\Payment\PaymentRepository $paymentRepository
 * @property \Shopsys\ShopBundle\Model\Transport\TransportRepository $transportRepository
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $em, \Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyRepository $currencyRepository, \Shopsys\FrameworkBundle\Model\Pricing\PricingSetting $pricingSetting, \Shopsys\ShopBundle\Model\Order\OrderRepository $orderRepository, \Shopsys\FrameworkBundle\Component\Domain\Domain $domain, \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculationScheduler $productPriceRecalculationScheduler, \Shopsys\ShopBundle\Model\Payment\PaymentRepository $paymentRepository, \Shopsys\ShopBundle\Model\Transport\TransportRepository $transportRepository, \Shopsys\FrameworkBundle\Model\Payment\PaymentPriceFactoryInterface $paymentPriceFactory, \Shopsys\FrameworkBundle\Model\Transport\TransportPriceFactoryInterface $transportPriceFactory, \Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFactoryInterface $currencyFactory)
 */
class CurrencyFacade extends BaseCurrencyFacade
{
    /**
     * @param string $code
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency|null
     */
    public function findByCode(string $code): ?Currency
    {
        return $this->currencyRepository->findByCode($code);
    }
}
