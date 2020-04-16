<?php

declare(strict_types=1);

namespace Tests\App\Functional\Model\Payment;

use App\Model\Payment\IndependentPaymentVisibilityCalculation;
use App\Model\Payment\Payment;
use Shopsys\FrameworkBundle\Model\Localization\Localization;
use Shopsys\FrameworkBundle\Model\Payment\PaymentDataFactoryInterface;
use Tests\App\Test\TransactionFunctionalTestCase;

class IndependentPaymentVisibilityCalculationTest extends TransactionFunctionalTestCase
{
    protected const FIRST_DOMAIN_ID = 1;
    protected const SECOND_DOMAIN_ID = 2;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Localization\Localization
     */
    private $localization;

    protected function setUp(): void
    {
        $this->localization = $this->getContainer()->get(Localization::class);
        parent::setUp();
    }

    public function testIsIndependentlyVisible()
    {
        $em = $this->getEntityManager();

        $enabledForDomains = [
            self::FIRST_DOMAIN_ID => true,
            self::SECOND_DOMAIN_ID => true,
        ];
        $payment = $this->getDefaultPayment($enabledForDomains, false);

        $em->persist($payment);
        $em->flush();

        /** @var \App\Model\Payment\IndependentPaymentVisibilityCalculation $independentPaymentVisibilityCalculation */
        $independentPaymentVisibilityCalculation =
            $this->getContainer()->get(IndependentPaymentVisibilityCalculation::class);

        $this->assertTrue($independentPaymentVisibilityCalculation->isIndependentlyVisible($payment, self::FIRST_DOMAIN_ID));
    }

    public function testIsIndependentlyVisibleEmptyName()
    {
        $em = $this->getEntityManager();

        $paymentData = $this->getPaymentDataFactory()->create();
        $names = [];
        foreach ($this->localization->getLocalesOfAllDomains() as $locale) {
            $names[$locale] = null;
        }
        $paymentData->name = $names;
        $paymentData->hidden = false;
        $paymentData->enabled = [
            self::FIRST_DOMAIN_ID => true,
            self::SECOND_DOMAIN_ID => false,
        ];

        $payment = new Payment($paymentData);

        $em->persist($payment);
        $em->flush();

        /** @var \App\Model\Payment\IndependentPaymentVisibilityCalculation $independentPaymentVisibilityCalculation */
        $independentPaymentVisibilityCalculation =
            $this->getContainer()->get(IndependentPaymentVisibilityCalculation::class);

        $this->assertFalse($independentPaymentVisibilityCalculation->isIndependentlyVisible($payment, self::FIRST_DOMAIN_ID));
    }

    public function testIsIndependentlyVisibleNotOnDomain()
    {
        $em = $this->getEntityManager();

        $enabledForDomains = [
            self::FIRST_DOMAIN_ID => false,
            self::SECOND_DOMAIN_ID => false,
        ];
        $payment = $this->getDefaultPayment($enabledForDomains, false);

        $em->persist($payment);
        $em->flush();

        /** @var \App\Model\Payment\IndependentPaymentVisibilityCalculation $independentPaymentVisibilityCalculation */
        $independentPaymentVisibilityCalculation =
            $this->getContainer()->get(IndependentPaymentVisibilityCalculation::class);

        $this->assertFalse($independentPaymentVisibilityCalculation->isIndependentlyVisible($payment, self::FIRST_DOMAIN_ID));
    }

    public function testIsIndependentlyVisibleHidden()
    {
        $em = $this->getEntityManager();

        $enabledForDomains = [
            self::FIRST_DOMAIN_ID => false,
            self::SECOND_DOMAIN_ID => false,
        ];
        $payment = $this->getDefaultPayment($enabledForDomains, false);

        $em->persist($payment);
        $em->flush();

        /** @var \App\Model\Payment\IndependentPaymentVisibilityCalculation $independentPaymentVisibilityCalculation */
        $independentPaymentVisibilityCalculation =
            $this->getContainer()->get(IndependentPaymentVisibilityCalculation::class);

        $this->assertFalse($independentPaymentVisibilityCalculation->isIndependentlyVisible($payment, self::FIRST_DOMAIN_ID));
    }

    /**
     * @param bool[] $enabledForDomains
     * @param bool $hidden
     * @return \App\Model\Payment\Payment
     */
    public function getDefaultPayment($enabledForDomains, $hidden)
    {
        $paymentDataFactory = $this->getPaymentDataFactory();

        $paymentData = $paymentDataFactory->create();
        $names = [];
        foreach ($this->localization->getLocalesOfAllDomains() as $locale) {
            $names[$locale] = 'paymentName';
        }
        $paymentData->name = $names;
        $paymentData->hidden = $hidden;
        $paymentData->enabled = $enabledForDomains;

        return new Payment($paymentData);
    }

    /**
     * @return \App\Model\Payment\PaymentDataFactory
     */
    public function getPaymentDataFactory()
    {
        return $this->getContainer()->get(PaymentDataFactoryInterface::class);
    }
}
