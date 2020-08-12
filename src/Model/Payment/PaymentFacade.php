<?php

declare(strict_types=1);

namespace App\Model\Payment;

use App\Model\GoPay\PaymentMethod\GoPayPaymentMethod;
use App\Model\Transport\Transport;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Payment\Exception\PaymentPriceNotFoundException;
use Shopsys\FrameworkBundle\Model\Payment\PaymentFacade as BasePaymentFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency;

/**
 * @property \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
 * @property \App\Model\Transport\TransportRepository $transportRepository
 * @property \App\Component\Image\ImageFacade $imageFacade
 * @property \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
 * @property \App\Model\Payment\PaymentPriceCalculation $paymentPriceCalculation
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $em, \App\Model\Payment\PaymentRepository $paymentRepository, \App\Model\Transport\TransportRepository $transportRepository, \Shopsys\FrameworkBundle\Model\Payment\PaymentVisibilityCalculation $paymentVisibilityCalculation, \Shopsys\FrameworkBundle\Component\Domain\Domain $domain, \App\Component\Image\ImageFacade $imageFacade, \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade, \App\Model\Payment\PaymentPriceCalculation $paymentPriceCalculation, \Shopsys\FrameworkBundle\Model\Payment\PaymentFactoryInterface $paymentFactory, \Shopsys\FrameworkBundle\Model\Payment\PaymentPriceFactoryInterface $paymentPriceFactory)
 * @method \App\Model\Payment\Payment create(\App\Model\Payment\PaymentData $paymentData)
 * @method edit(\App\Model\Payment\Payment $payment, \App\Model\Payment\PaymentData $paymentData)
 * @method \App\Model\Payment\Payment getById(int $id)
 * @method setAdditionalDataAndFlush(\App\Model\Payment\Payment $payment, \App\Model\Payment\PaymentData $paymentData)
 * @method \App\Model\Payment\Payment[] getVisibleByDomainId(int $domainId)
 * @method \App\Model\Payment\Payment[] getAllIncludingDeleted()
 * @method \App\Model\Payment\Payment[] getAll()
 * @method \Shopsys\FrameworkBundle\Model\Pricing\Price[] getIndependentBasePricesIndexedByDomainId(\App\Model\Payment\Payment $payment)
 * @method \Shopsys\FrameworkBundle\Model\Pricing\Price[] getPricesIndexedByDomainId(\App\Model\Payment\Payment|null $payment)
 * @method updatePaymentPrices(\App\Model\Payment\Payment $payment, \Shopsys\FrameworkBundle\Component\Money\Money[] $pricesIndexedByDomainId, \App\Model\Pricing\Vat\Vat[] $vatsIndexedByDomainId)
 * @method \App\Model\Payment\Payment getByUuid(string $uuid)
 * @method \App\Model\Payment\Payment[] getVisibleOnCurrentDomain()
 */
class PaymentFacade extends BasePaymentFacade
{
    /**
     * @var \App\Model\Payment\PaymentRepository
     */
    protected $paymentRepository;

    /**
     * @param \App\Model\GoPay\PaymentMethod\GoPayPaymentMethod $goPayPaymentMethod
     */
    public function hideByGoPayPaymentMethod(GoPayPaymentMethod $goPayPaymentMethod): void
    {
        $payments = $this->paymentRepository->getByGoPayPaymentMethod($goPayPaymentMethod);

        foreach ($payments as $payment) {
            $payment->hideByGoPay();
        }

        $this->em->flush($payments);
    }

    /**
     * @param string $type
     * @return \App\Model\Payment\Payment|null
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
     * @param \App\Model\Pricing\Currency\Currency $currency
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Component\Money\Money[]
     */
    public function getPaymentPricesWithVatByCurrencyAndDomainIdIndexedByPaymentId(Currency $currency, int $domainId): array
    {
        $paymentPricesWithVatByPaymentId = [];
        $payments = $this->getAllIncludingDeleted();
        foreach ($payments as $payment) {
            try {
                $paymentPrice = $this->paymentPriceCalculation->calculateIndependentPrice($payment, $currency, $domainId);
                $paymentPricesWithVatByPaymentId[$payment->getId()] = $paymentPrice->getPriceWithVat();
            } catch (PaymentPriceNotFoundException $exception) {
                $paymentPricesWithVatByPaymentId[$payment->getId()] = Money::zero();
            }
        }

        return $paymentPricesWithVatByPaymentId;
    }

    /**
     * @param \App\Model\GoPay\PaymentMethod\GoPayPaymentMethod $goPayPaymentMethod
     */
    public function unHideByGoPayPaymentMethod(GoPayPaymentMethod $goPayPaymentMethod): void
    {
        $payments = $this->paymentRepository->getByGoPayPaymentMethod($goPayPaymentMethod);

        foreach ($payments as $payment) {
            $payment->unHideByGoPay();
        }
        $this->em->flush($payments);
    }

    /**
     * @param \App\Model\Transport\Transport $transport
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $orderPrice
     * @return \App\Model\Payment\Payment[]
     */
    public function getVisibleOnCurrentDomainByTransport(Transport $transport, ?Money $orderPrice = null): array
    {
        $paymentsByTransport = $this->paymentRepository->getAllByTransport($transport);
        /** @var \App\Model\Payment\Payment[] $payments */
        $payments = $this->paymentVisibilityCalculation->filterVisible($paymentsByTransport, $this->domain->getId());

        if ($orderPrice !== null) {
            $payments = $this->filterPaymentsByOrderPrice($payments, $orderPrice);
        }

        return $payments;
    }

    /**
     * @param int $domainId
     * @param bool $onlyUsableForGiftCertificates
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $orderPrice
     * @return \App\Model\Payment\Payment[]
     */
    public function getVisibleByDomainIdAndGiftCertificateUsabilityAndPrice(int $domainId, bool $onlyUsableForGiftCertificates, Money $orderPrice): array
    {
        $payments = $this->getVisibleByDomainId($domainId);
        $payments = $this->filterPaymentsByOrderPrice($payments, $orderPrice);

        if (!$onlyUsableForGiftCertificates) {
            return $payments;
        }

        return array_filter($payments, function (Payment $payment) use ($onlyUsableForGiftCertificates) {
            return $payment->isUsableForGiftCertificates() === $onlyUsableForGiftCertificates;
        });
    }

    /**
     * @param \App\Model\Payment\Payment[] $payments
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $orderPrice
     * @return \App\Model\Payment\Payment[]
     */
    private function filterPaymentsByOrderPrice(array $payments, Money $orderPrice): array
    {
        $filteredPayments = [];
        foreach ($payments as $payment) {
            $minimumOrderPrice = $payment->getMinimumOrderPrice($this->domain->getId());
            if ($minimumOrderPrice->isZero() || $minimumOrderPrice->isLessThan($orderPrice)) {
                $filteredPayments[] = $payment;
            }
        }

        return $filteredPayments;
    }

    /**
     * @param string $paymentName
     * @param bool $czkRounding
     * @param string $locale
     * @return \App\Model\Payment\Payment
     */
    public function findByNameAndCzkRounding(string $paymentName, bool $czkRounding, string $locale): ?Payment
    {
        return $this->paymentRepository->findByNameAndCzkRounding($paymentName, $czkRounding, $locale);
    }
}
