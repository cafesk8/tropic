<?php

declare(strict_types=1);

namespace Tests\App\Functional\Model\Vat;

use App\DataFixtures\Demo\PaymentDataFixture;
use App\DataFixtures\Demo\TransportDataFixture;
use App\DataFixtures\Demo\VatDataFixture;
use App\Model\Pricing\Vat\VatData;
use App\Model\Pricing\Vat\VatFacade;
use App\Model\Transport\TransportFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Payment\PaymentDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Payment\PaymentFacade;
use Shopsys\FrameworkBundle\Model\Transport\TransportDataFactoryInterface;
use Tests\App\Test\TransactionFunctionalTestCase;

class VatFacadeTest extends TransactionFunctionalTestCase
{
    public function testDeleteByIdAndReplaceForFirstDomain()
    {
        $em = $this->getEntityManager();
        /** @var \App\Model\Pricing\Vat\VatFacade $vatFacade */
        $vatFacade = $this->getContainer()->get(VatFacade::class);
        /** @var \App\Model\Transport\TransportFacade $transportFacade */
        $transportFacade = $this->getContainer()->get(TransportFacade::class);
        /** @var \App\Model\Transport\TransportDataFactory $transportDataFactory */
        $transportDataFactory = $this->getContainer()->get(TransportDataFactoryInterface::class);
        /** @var \App\Model\Payment\PaymentDataFactory $paymentDataFactory */
        $paymentDataFactory = $this->getContainer()->get(PaymentDataFactoryInterface::class);
        /** @var \Shopsys\FrameworkBundle\Model\Payment\PaymentFacade $paymentFacade */
        $paymentFacade = $this->getContainer()->get(PaymentFacade::class);

        $vatData = new VatData();
        $vatData->name = 'name';
        $vatData->percent = '10';
        $vatToDelete = $vatFacade->create($vatData, Domain::FIRST_DOMAIN_ID);
        /** @var \Shopsys\FrameworkBundle\Model\Pricing\Vat\Vat $vatToReplaceWith */
        $vatToReplaceWith = $this->getReferenceForDomain(VatDataFixture::VAT_HIGH, Domain::FIRST_DOMAIN_ID);
        /** @var \App\Model\Transport\Transport $transport */
        $transport = $this->getReference(TransportDataFixture::TRANSPORT_PERSONAL);
        $transportData = $transportDataFactory->createFromTransport($transport);
        /** @var \App\Model\Payment\Payment $payment */
        $payment = $this->getReference(PaymentDataFixture::PAYMENT_CASH);
        $paymentData = $paymentDataFactory->createFromPayment($payment);

        $transportData->vatsIndexedByDomainId[Domain::FIRST_DOMAIN_ID] = $vatToDelete;
        $transportFacade->edit($transport, $transportData);

        $paymentData->vatsIndexedByDomainId[Domain::FIRST_DOMAIN_ID] = $vatToDelete;
        $paymentFacade->edit($payment, $paymentData);

        $vatFacade->deleteById($vatToDelete, $vatToReplaceWith);

        $em->refresh($transport->getTransportDomain(Domain::FIRST_DOMAIN_ID));
        $em->refresh($payment->getPaymentDomain(Domain::FIRST_DOMAIN_ID));

        $this->assertEquals($vatToReplaceWith, $payment->getPaymentDomain(Domain::FIRST_DOMAIN_ID)->getVat());
        $this->assertEquals($vatToReplaceWith, $transport->getTransportDomain(Domain::FIRST_DOMAIN_ID)->getVat());
    }
}
