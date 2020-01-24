<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Payment;

use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Payment\Exception\PaymentPriceNotFoundException;
use Shopsys\FrameworkBundle\Model\Payment\PaymentFacade as BasePaymentFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency;
use Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethod;

/**
 * @property \Shopsys\ShopBundle\Model\Transport\TransportRepository $transportRepository
 * @property \Shopsys\ShopBundle\Component\Image\ImageFacade $imageFacade
 * @property \Shopsys\ShopBundle\Model\Pricing\Currency\CurrencyFacade $currencyFacade
 * @property \Shopsys\ShopBundle\Model\Payment\PaymentPriceCalculation $paymentPriceCalculation
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $em, \Shopsys\ShopBundle\Model\Payment\PaymentRepository $paymentRepository, \Shopsys\ShopBundle\Model\Transport\TransportRepository $transportRepository, \Shopsys\FrameworkBundle\Model\Payment\PaymentVisibilityCalculation $paymentVisibilityCalculation, \Shopsys\FrameworkBundle\Component\Domain\Domain $domain, \Shopsys\ShopBundle\Component\Image\ImageFacade $imageFacade, \Shopsys\ShopBundle\Model\Pricing\Currency\CurrencyFacade $currencyFacade, \Shopsys\ShopBundle\Model\Payment\PaymentPriceCalculation $paymentPriceCalculation, \Shopsys\FrameworkBundle\Model\Payment\PaymentFactoryInterface $paymentFactory, \Shopsys\FrameworkBundle\Model\Payment\PaymentPriceFactoryInterface $paymentPriceFactory)
 * @method \Shopsys\ShopBundle\Model\Payment\Payment create(\Shopsys\ShopBundle\Model\Payment\PaymentData $paymentData)
 * @method edit(\Shopsys\ShopBundle\Model\Payment\Payment $payment, \Shopsys\ShopBundle\Model\Payment\PaymentData $paymentData)
 * @method \Shopsys\ShopBundle\Model\Payment\Payment getById(int $id)
 * @method setAdditionalDataAndFlush(\Shopsys\ShopBundle\Model\Payment\Payment $payment, \Shopsys\ShopBundle\Model\Payment\PaymentData $paymentData)
 * @method \Shopsys\ShopBundle\Model\Payment\Payment[] getVisibleOnCurrentDomain()
 * @method \Shopsys\ShopBundle\Model\Payment\Payment[] getVisibleByDomainId(int $domainId)
 * @method updatePaymentPrices(\Shopsys\ShopBundle\Model\Payment\Payment $payment, \Shopsys\FrameworkBundle\Component\Money\Money[] $pricesByCurrencyId)
 * @method \Shopsys\ShopBundle\Model\Payment\Payment[] getAllIncludingDeleted()
 * @method \Shopsys\ShopBundle\Model\Payment\Payment[] getAll()
 * @method \Shopsys\FrameworkBundle\Model\Pricing\Price[] getIndependentBasePricesIndexedByCurrencyId(\Shopsys\ShopBundle\Model\Payment\Payment $payment)
 */
class PaymentFacade extends BasePaymentFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Payment\PaymentRepository
     */
    protected $paymentRepository;

    /**
     * @param \Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethod $goPayPaymentMethod
     */
    public function hideByGoPayPaymentMethod(GoPayPaymentMethod $goPayPaymentMethod): void
    {
        $payments = $this->paymentRepository->getByGoPayPaymentMethod($goPayPaymentMethod);

        foreach ($payments as $payment) {
            $payment->hide();
        }

        $this->em->flush($payments);
    }

    /**
     * @param string $type
     * @return \Shopsys\ShopBundle\Model\Payment\Payment|null
     */
    public function getFirstPaymentByType(string $type): ?Payment
    {
        $paymentsByType = $this->paymentRepository->getByType($type);

        if (count($paymentsByType) > 0) {
            return $paymentsByType[0];
        }

        return null;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency $currency
     * @return \Shopsys\FrameworkBundle\Component\Money\Money[]
     */
    public function getPaymentPricesWithVatIndexedByPaymentId(Currency $currency): array
    {
        $paymentPricesWithVatByPaymentId = [];
        $payments = $this->getAllIncludingDeleted();
        foreach ($payments as $payment) {
            try {
                $paymentPrice = $this->paymentPriceCalculation->calculateIndependentPrice($payment, $currency);
                $paymentPricesWithVatByPaymentId[$payment->getId()] = $paymentPrice->getPriceWithVat();
            } catch (PaymentPriceNotFoundException $exception) {
                $paymentPricesWithVatByPaymentId[$payment->getId()] = Money::zero();
            }
        }

        return $paymentPricesWithVatByPaymentId;
    }
}
