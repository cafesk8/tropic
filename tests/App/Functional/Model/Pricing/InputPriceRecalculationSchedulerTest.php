<?php

declare(strict_types=1);

namespace Tests\App\Functional\Model\Pricing;

use App\Component\Setting\Setting;
use App\Model\Pricing\Vat\Vat;
use App\Model\Pricing\Vat\VatData;
use App\Model\Product\Availability\Availability;
use App\Model\Product\Availability\AvailabilityData;
use App\Model\Transport\TransportFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Payment\PaymentDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Payment\PaymentFacade;
use Shopsys\FrameworkBundle\Model\Pricing\InputPriceRecalculationScheduler;
use Shopsys\FrameworkBundle\Model\Pricing\InputPriceRecalculator;
use Shopsys\FrameworkBundle\Model\Pricing\PricingSetting;
use Shopsys\FrameworkBundle\Model\Transport\TransportDataFactoryInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Tests\App\Test\TransactionFunctionalTestCase;
use Tests\FrameworkBundle\Test\IsMoneyEqual;

class InputPriceRecalculationSchedulerTest extends TransactionFunctionalTestCase
{
    private const METHOD_WITH_VAT = 'scheduleSetInputPricesWithVat';
    private const METHOD_WITHOUT_VAT = 'scheduleSetInputPricesWithoutVat';

    public function testOnKernelResponseNoAction()
    {
        /** @var \Shopsys\FrameworkBundle\Component\Setting\Setting $setting */
        $setting = $this->getContainer()->get(Setting::class);

        $inputPriceRecalculatorMock = $this->getMockBuilder(InputPriceRecalculator::class)
            ->setMethods(['__construct', 'recalculateToInputPricesWithoutVat', 'recalculateToInputPricesWithVat'])
            ->disableOriginalConstructor()
            ->getMock();
        $inputPriceRecalculatorMock->expects($this->never())->method('recalculateToInputPricesWithoutVat');
        $inputPriceRecalculatorMock->expects($this->never())->method('recalculateToInputPricesWithVat');

        $filterResponseEventMock = $this->getMockBuilder(FilterResponseEvent::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMasterRequest'])
            ->getMock();
        $filterResponseEventMock->expects($this->any())->method('isMasterRequest')
            ->willReturn(true);

        $inputPriceRecalculationScheduler = new InputPriceRecalculationScheduler($inputPriceRecalculatorMock, $setting);

        $inputPriceRecalculationScheduler->onKernelResponse($filterResponseEventMock);
    }

    public function inputPricesTestDataProvider()
    {
        return [
            [
                'inputPriceWithoutVat' => Money::create(100),
                'inputPriceWithVat' => Money::create(121),
                'vatPercent' => '21',
            ],
            [
                'inputPriceWithoutVat' => Money::create('17261.983471'),
                'inputPriceWithVat' => Money::create(20887),
                'vatPercent' => '21',
            ],
        ];
    }

    /**
     * @dataProvider inputPricesTestDataProvider
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $inputPriceWithoutVat
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $inputPriceWithVat
     * @param mixed $vatPercent
     */
    public function testOnKernelResponseRecalculateInputPricesWithoutVat(
        Money $inputPriceWithoutVat,
        Money $inputPriceWithVat,
        $vatPercent
    ) {
        /** @var \Shopsys\FrameworkBundle\Component\Setting\Setting $setting */
        $setting = $this->getContainer()->get(Setting::class);

        $setting->set(PricingSetting::INPUT_PRICE_TYPE, PricingSetting::INPUT_PRICE_TYPE_WITH_VAT);

        $this->doTestOnKernelResponseRecalculateInputPrices($inputPriceWithVat, $inputPriceWithoutVat, $vatPercent, self::METHOD_WITHOUT_VAT);
    }

    /**
     * @dataProvider inputPricesTestDataProvider
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $inputPriceWithoutVat
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $inputPriceWithVat
     * @param mixed $vatPercent
     */
    public function testOnKernelResponseRecalculateInputPricesWithVat(
        Money $inputPriceWithoutVat,
        Money $inputPriceWithVat,
        $vatPercent
    ) {
        /** @var \Shopsys\FrameworkBundle\Component\Setting\Setting $setting */
        $setting = $this->getContainer()->get(Setting::class);

        $setting->set(PricingSetting::INPUT_PRICE_TYPE, PricingSetting::INPUT_PRICE_TYPE_WITHOUT_VAT);

        $this->doTestOnKernelResponseRecalculateInputPrices($inputPriceWithoutVat, $inputPriceWithVat, $vatPercent, self::METHOD_WITH_VAT);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $inputPrice
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $expectedPrice
     * @param mixed $vatPercent
     * @param string $scheduleSetInputPricesMethod
     */
    private function doTestOnKernelResponseRecalculateInputPrices(Money $inputPrice, Money $expectedPrice, $vatPercent, string $scheduleSetInputPricesMethod): void
    {
        $em = $this->getEntityManager();
        /** @var \Shopsys\FrameworkBundle\Model\Pricing\InputPriceRecalculationScheduler $inputPriceRecalculationScheduler */
        $inputPriceRecalculationScheduler = $this->getContainer()->get(InputPriceRecalculationScheduler::class);
        /** @var \Shopsys\FrameworkBundle\Model\Payment\PaymentFacade $paymentFacade */
        $paymentFacade = $this->getContainer()->get(PaymentFacade::class);
        /** @var \App\Model\Transport\TransportFacade $transportFacade */
        $transportFacade = $this->getContainer()->get(TransportFacade::class);
        /** @var \App\Model\Payment\PaymentDataFactory $paymentDataFactory */
        $paymentDataFactory = $this->getContainer()->get(PaymentDataFactoryInterface::class);
        /** @var \App\Model\Transport\TransportDataFactory $transportDataFactory */
        $transportDataFactory = $this->getContainer()->get(TransportDataFactoryInterface::class);

        $paymentData = $paymentDataFactory->create();
        $transportData = $transportDataFactory->create();

        $paymentData->pricesIndexedByDomainId[Domain::FIRST_DOMAIN_ID] = $inputPrice;
        $transportData->pricesIndexedByDomainId[Domain::FIRST_DOMAIN_ID] = $inputPrice;

        $vatData = new VatData();
        $vatData->name = 'vat';
        $vatData->percent = $vatPercent;
        $vat = new Vat($vatData, Domain::FIRST_DOMAIN_ID);
        $availabilityData = new AvailabilityData();
        $availabilityData->dispatchTime = 0;
        $availability = new Availability($availabilityData);
        $em->persist($vat);
        $em->persist($availability);

        $paymentData->name = ['cs' => 'name'];

        /** @var \App\Model\Payment\Payment $payment */
        $payment = $paymentFacade->create($paymentData);

        $transportData->name = ['cs' => 'name'];
        $transportData->description = ['cs' => 'desc'];
        /** @var \App\Model\Transport\Transport $transport */
        $transport = $transportFacade->create($transportData);

        $em->flush();

        $filterResponseEventMock = $this->getMockBuilder(FilterResponseEvent::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMasterRequest'])
            ->getMock();
        $filterResponseEventMock->expects($this->any())->method('isMasterRequest')
            ->willReturn(true);

        if ($scheduleSetInputPricesMethod === self::METHOD_WITH_VAT) {
            $inputPriceRecalculationScheduler->scheduleSetInputPricesWithVat();
        } elseif ($scheduleSetInputPricesMethod === self::METHOD_WITHOUT_VAT) {
            $inputPriceRecalculationScheduler->scheduleSetInputPricesWithoutVat();
        }

        $inputPriceRecalculationScheduler->onKernelResponse($filterResponseEventMock);

        $em->refresh($payment);
        $em->refresh($transport);

        $this->assertThat($payment->getPrice(Domain::FIRST_DOMAIN_ID)->getPrice(), new IsMoneyEqual($expectedPrice));
        $this->assertThat($transport->getPrice(Domain::FIRST_DOMAIN_ID)->getPrice(), new IsMoneyEqual($expectedPrice));
    }
}
