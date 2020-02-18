<?php

declare(strict_types=1);

namespace Tests\App\Functional\Model\Payment;

use App\Model\Payment\Payment;
use App\Model\Transport\Transport;
use App\Model\Transport\TransportFacade;
use Shopsys\FrameworkBundle\Model\Payment\PaymentDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Transport\TransportDataFactoryInterface;
use Tests\App\Test\TransactionFunctionalTestCase;

class PaymentTest extends TransactionFunctionalTestCase
{
    public function testRemoveTransportFromPaymentAfterDelete()
    {
        /** @var \App\Model\Payment\PaymentDataFactory $paymentDataFactory */
        $paymentDataFactory = $this->getContainer()->get(PaymentDataFactoryInterface::class);
        /** @var \App\Model\Transport\TransportDataFactory $transportDataFactory */
        $transportDataFactory = $this->getContainer()->get(TransportDataFactoryInterface::class);
        $em = $this->getEntityManager();

        $transportData = $transportDataFactory->create();
        $transportData->name['cs'] = 'name';
        $transport = new Transport($transportData);

        $paymentData = $paymentDataFactory->create();
        $paymentData->name['cs'] = 'name';

        $payment = new Payment($paymentData);
        $payment->addTransport($transport);

        $em->persist($transport);
        $em->persist($payment);
        $em->flush();

        /** @var \App\Model\Transport\TransportFacade $transportFacade */
        $transportFacade = $this->getContainer()->get(TransportFacade::class);
        $transportFacade->deleteById($transport->getId());

        $this->assertNotContains($transport, $payment->getTransports());
    }
}
