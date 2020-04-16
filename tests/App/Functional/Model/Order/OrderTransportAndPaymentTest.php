<?php

declare(strict_types=1);

namespace Tests\App\Functional\Model\Order;

use App\Model\Payment\Payment;
use App\Model\Payment\PaymentFacade;
use App\Model\Transport\Transport;
use App\Model\Transport\TransportFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Payment\PaymentDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Transport\TransportDataFactoryInterface;
use Tests\App\Test\TransactionFunctionalTestCase;

class OrderTransportAndPaymentTest extends TransactionFunctionalTestCase
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    protected function setUp(): void
    {
        $this->domain = $this->getContainer()->get(Domain::class);
        parent::setUp();
    }

    public function testVisibleTransport()
    {
        $em = $this->getEntityManager();

        $enabledForDomains = [
            Domain::FIRST_DOMAIN_ID => true,
            Domain::SECOND_DOMAIN_ID => false,
        ];
        $transport = $this->getDefaultTransport($enabledForDomains, false);
        $payment = $this->getDefaultPayment($enabledForDomains, false);

        $payment->addTransport($transport);

        $em->persist($transport);
        $em->flush();
        $em->persist($payment);
        $em->flush();

        /** @var \App\Model\Transport\TransportFacade $transportFacade */
        $transportFacade = $this->getContainer()->get(TransportFacade::class);
        /** @var \App\Model\Payment\PaymentFacade $paymentFacade */
        $paymentFacade = $this->getContainer()->get(PaymentFacade::class);

        $visiblePayments = $paymentFacade->getVisibleOnCurrentDomain();
        $visibleTransports = $transportFacade->getVisibleOnCurrentDomain($visiblePayments);

        $this->assertContains($transport, $visibleTransports);
    }

    public function testVisibleTransportHiddenTransport()
    {
        $em = $this->getEntityManager();

        $enabledOnDomains = [
            1 => true,
            2 => false,
        ];
        $transport = $this->getDefaultTransport($enabledOnDomains, true);
        $payment = $this->getDefaultPayment($enabledOnDomains, false);

        $payment->addTransport($transport);

        $em->persist($transport);
        $em->persist($payment);
        $em->flush();

        /** @var \App\Model\Transport\TransportFacade $transportFacade */
        $transportFacade = $this->getContainer()->get(TransportFacade::class);
        /** @var \App\Model\Payment\PaymentFacade $paymentFacade */
        $paymentFacade = $this->getContainer()->get(PaymentFacade::class);

        $visiblePayments = $paymentFacade->getVisibleOnCurrentDomain();
        $visibleTransports = $transportFacade->getVisibleOnCurrentDomain($visiblePayments);

        $this->assertNotContains($transport, $visibleTransports);
    }

    public function testVisibleTransportHiddenPayment()
    {
        $em = $this->getEntityManager();

        $transportEnabledForDomains = [
            1 => true,
            2 => false,
        ];
        $paymentEnabledForDomains = [
            1 => false,
            2 => false,
        ];

        $transport = $this->getDefaultTransport($transportEnabledForDomains, false);
        $payment = $this->getDefaultPayment($paymentEnabledForDomains, true);

        $payment->addTransport($transport);

        $em->persist($transport);
        $em->persist($payment);
        $em->flush();

        /** @var \App\Model\Transport\TransportFacade $transportFacade */
        $transportFacade = $this->getContainer()->get(TransportFacade::class);
        /** @var \App\Model\Payment\PaymentFacade $paymentFacade */
        $paymentFacade = $this->getContainer()->get(PaymentFacade::class);

        $visiblePayments = $paymentFacade->getVisibleOnCurrentDomain();
        $visibleTransports = $transportFacade->getVisibleOnCurrentDomain($visiblePayments);

        $this->assertNotContains($transport, $visibleTransports);
    }

    public function testVisibleTransportNoPayment()
    {
        $em = $this->getEntityManager();

        $enabledForDomains = [
            1 => true,
            2 => false,
        ];
        $transport = $this->getDefaultTransport($enabledForDomains, false);

        $em->persist($transport);
        $em->flush();

        /** @var \App\Model\Transport\TransportFacade $transportFacade */
        $transportFacade = $this->getContainer()->get(TransportFacade::class);
        /** @var \App\Model\Payment\PaymentFacade $paymentFacade */
        $paymentFacade = $this->getContainer()->get(PaymentFacade::class);

        $visiblePayments = $paymentFacade->getVisibleOnCurrentDomain();
        $visibleTransports = $transportFacade->getVisibleOnCurrentDomain($visiblePayments);

        $this->assertNotContains($transport, $visibleTransports);
    }

    public function testVisibleTransportOnDifferentDomain()
    {
        $em = $this->getEntityManager();

        $paymentEnabledForDomains = [
            1 => true,
            2 => false,
        ];
        $transportEnabledForDomains = [
            1 => false,
            2 => true,
        ];

        $transport = $this->getDefaultTransport($transportEnabledForDomains, false);
        $payment = $this->getDefaultPayment($paymentEnabledForDomains, false);

        $payment->addTransport($transport);

        $em->persist($transport);
        $em->persist($payment);
        $em->flush();

        /** @var \App\Model\Transport\TransportFacade $transportFacade */
        $transportFacade = $this->getContainer()->get(TransportFacade::class);
        /** @var \App\Model\Payment\PaymentFacade $paymentFacade */
        $paymentFacade = $this->getContainer()->get(PaymentFacade::class);

        $visiblePayments = $paymentFacade->getVisibleOnCurrentDomain();
        $visibleTransports = $transportFacade->getVisibleOnCurrentDomain($visiblePayments);

        $this->assertNotContains($transport, $visibleTransports);
    }

    public function testVisibleTransportPaymentOnDifferentDomain()
    {
        $em = $this->getEntityManager();

        $paymentEnabledForDomains = [
            1 => false,
            2 => true,
        ];
        $transportEnabledForDomains = [
            1 => true,
            2 => false,
        ];
        $transport = $this->getDefaultTransport($transportEnabledForDomains, false);
        $payment = $this->getDefaultPayment($paymentEnabledForDomains, false);
        $payment->addTransport($transport);

        $em->persist($transport);
        $em->persist($payment);
        $em->flush();

        /** @var \App\Model\Transport\TransportFacade $transportFacade */
        $transportFacade = $this->getContainer()->get(TransportFacade::class);
        /** @var \App\Model\Payment\PaymentFacade $paymentFacade */
        $paymentFacade = $this->getContainer()->get(PaymentFacade::class);

        $visiblePayments = $paymentFacade->getVisibleOnCurrentDomain();
        $visibleTransports = $transportFacade->getVisibleOnCurrentDomain($visiblePayments);

        $this->assertNotContains($transport, $visibleTransports);
    }

    public function testVisiblePayment()
    {
        $em = $this->getEntityManager();

        $enabledForDomains = [
            1 => true,
            2 => false,
        ];
        $transport = $this->getDefaultTransport($enabledForDomains, false);
        $payment = $this->getDefaultPayment($enabledForDomains, false);

        $payment->addTransport($transport);

        $em->persist($transport);
        $em->persist($payment);
        $em->flush();

        /** @var \Shopsys\FrameworkBundle\Model\Payment\PaymentFacade $paymentFacade */
        $paymentFacade = $this->getContainer()->get(PaymentFacade::class);

        $visiblePayments = $paymentFacade->getVisibleOnCurrentDomain();

        $this->assertContains($payment, $visiblePayments);
    }

    public function testVisiblePaymentHiddenTransport()
    {
        $em = $this->getEntityManager();

        $enabledForDomains = [
            1 => true,
            2 => false,
        ];
        $transport = $this->getDefaultTransport($enabledForDomains, true);
        $payment = $this->getDefaultPayment($enabledForDomains, false);

        $payment->addTransport($transport);

        $em->persist($transport);
        $em->persist($payment);
        $em->flush();

        /** @var \Shopsys\FrameworkBundle\Model\Payment\PaymentFacade $paymentFacade */
        $paymentFacade = $this->getContainer()->get(PaymentFacade::class);

        $visiblePayments = $paymentFacade->getVisibleOnCurrentDomain();

        $this->assertNotContains($payment, $visiblePayments);
    }

    public function testVisiblePaymentHiddenPayment()
    {
        $em = $this->getEntityManager();

        $enabledForDomains = [
            1 => true,
            2 => false,
        ];
        $transport = $this->getDefaultTransport($enabledForDomains, false);
        $payment = $this->getDefaultPayment($enabledForDomains, true);

        $payment->addTransport($transport);

        $em->persist($transport);
        $em->persist($payment);
        $em->flush();

        /** @var \Shopsys\FrameworkBundle\Model\Payment\PaymentFacade $paymentFacade */
        $paymentFacade = $this->getContainer()->get(PaymentFacade::class);

        $visiblePayments = $paymentFacade->getVisibleOnCurrentDomain();

        $this->assertNotContains($payment, $visiblePayments);
    }

    public function testVisiblePaymentNoTransport()
    {
        $em = $this->getEntityManager();

        $enabledForDomains = [
            1 => true,
            2 => false,
        ];
        $payment = $this->getDefaultPayment($enabledForDomains, false);

        $em->persist($payment);
        $em->flush();

        /** @var \Shopsys\FrameworkBundle\Model\Payment\PaymentFacade $paymentFacade */
        $paymentFacade = $this->getContainer()->get(PaymentFacade::class);

        $visiblePayments = $paymentFacade->getVisibleOnCurrentDomain();

        $this->assertNotContains($payment, $visiblePayments);
    }

    public function testVisiblePaymentOnDifferentDomain()
    {
        $em = $this->getEntityManager();

        $transportEnabledForDomains = [
            1 => true,
            2 => false,
        ];
        $paymentEnabledForDomains = [
            1 => false,
            2 => true,
        ];
        $transport = $this->getDefaultTransport($transportEnabledForDomains, false);
        $payment = $this->getDefaultPayment($paymentEnabledForDomains, false);
        $payment->addTransport($transport);

        $em->persist($transport);
        $em->persist($payment);
        $em->flush();

        /** @var \Shopsys\FrameworkBundle\Model\Payment\PaymentFacade $paymentFacade */
        $paymentFacade = $this->getContainer()->get(PaymentFacade::class);

        $visiblePayments = $paymentFacade->getVisibleOnCurrentDomain();

        $this->assertNotContains($payment, $visiblePayments);
    }

    public function testVisiblePaymentTransportOnDifferentDomain()
    {
        $em = $this->getEntityManager();

        $transportEnabledForDomains = [
            1 => true,
            2 => false,
        ];
        $paymentEnabledForDomains = [
            1 => false,
            2 => true,
        ];
        $transport = $this->getDefaultTransport($transportEnabledForDomains, false);
        $payment = $this->getDefaultPayment($paymentEnabledForDomains, false);

        $payment->addTransport($transport);

        $em->persist($transport);
        $em->persist($payment);
        $em->flush();

        /** @var \Shopsys\FrameworkBundle\Model\Payment\PaymentFacade $paymentFacade */
        $paymentFacade = $this->getContainer()->get(PaymentFacade::class);

        $visiblePayments = $paymentFacade->getVisibleOnCurrentDomain();

        $this->assertNotContains($payment, $visiblePayments);
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
        foreach ($this->domain->getAllLocales() as $locale) {
            $names[$locale] = 'paymentName';
        }
        $paymentData->name = $names;
        $paymentData->hidden = $hidden;
        $paymentData->enabled = $enabledForDomains;

        return new Payment($paymentData);
    }

    /**
     * @param bool[] $enabledForDomains
     * @param bool $hidden
     * @return \App\Model\Transport\Transport
     */
    public function getDefaultTransport($enabledForDomains, $hidden)
    {
        $transportDataFactory = $this->getTransportDataFactory();

        $transportData = $transportDataFactory->create();
        $names = [];
        foreach ($this->domain->getAllLocales() as $locale) {
            $names[$locale] = 'transportName';
        }
        $transportData->name = $names;

        $transportData->hidden = $hidden;
        $transportData->enabled = $enabledForDomains;

        return new Transport($transportData);
    }

    /**
     * @return \App\Model\Payment\PaymentDataFactory
     */
    public function getPaymentDataFactory()
    {
        return $this->getContainer()->get(PaymentDataFactoryInterface::class);
    }

    /**
     * @return \App\Model\Transport\TransportDataFactory
     */
    public function getTransportDataFactory()
    {
        return $this->getContainer()->get(TransportDataFactoryInterface::class);
    }
}
