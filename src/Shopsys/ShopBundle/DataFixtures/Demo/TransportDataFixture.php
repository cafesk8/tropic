<?php

namespace Shopsys\ShopBundle\DataFixtures\Demo;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Transport\TransportData;
use Shopsys\FrameworkBundle\Model\Transport\TransportDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Transport\TransportFacade;
use Shopsys\ShopBundle\Form\Admin\TransportFormTypeExtension;

class TransportDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
{
    const TRANSPORT_CZECH_POST = 'transport_cp';
    const TRANSPORT_PPL = 'transport_ppl';
    const TRANSPORT_PERSONAL = 'transport_personal';

    /** @var \Shopsys\ShopBundle\Model\Transport\TransportFacade */
    protected $transportFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Transport\TransportDataFactoryInterface
     */
    protected $transportDataFactory;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param \Shopsys\ShopBundle\Model\Transport\TransportFacade $transportFacade
     * @param \Shopsys\FrameworkBundle\Model\Transport\TransportDataFactoryInterface $transportDataFactory
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     */
    public function __construct(
        TransportFacade $transportFacade,
        TransportDataFactoryInterface $transportDataFactory,
        EntityManagerInterface $entityManager
    ) {
        $this->transportFacade = $transportFacade;
        $this->transportDataFactory = $transportDataFactory;
        $this->entityManager = $entityManager;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /** @var \Shopsys\ShopBundle\Model\Transport\TransportData $transportData */
        $transportData = $this->transportDataFactory->create();
        $transportData->name = [
            'cs' => 'Česká pošta - balík do ruky',
            'sk' => 'Česká pošta - balík do ruky',
            'de' => 'Czech post',
        ];
        $transportData->pricesByCurrencyId = [
            $this->getReference(CurrencyDataFixture::CURRENCY_CZK)->getId() => Money::create('99.95'),
            $this->getReference(CurrencyDataFixture::CURRENCY_EUR)->getId() => Money::create('3.95'),
        ];
        $transportData->vat = $this->getReference(VatDataFixture::VAT_HIGH);
        $this->createTransport(self::TRANSPORT_CZECH_POST, $transportData);

        $transportData = $this->transportDataFactory->create();
        $transportData->name = [
            'cs' => 'PPL',
            'sk' => 'PPL',
            'de' => 'PPL',
        ];
        $transportData->personalTakeType = TransportFormTypeExtension::PERSONAL_TAKE_TYPE_BALIKOBOT;
        $transportData->balikobotShipper = TransportPickupPlaceDataFixture::BALIKOBOT_SHIPPER;
        $transportData->balikobotShipperService = TransportPickupPlaceDataFixture::BALIKOBOT_SHIPPER_SERVICE;
        $transportData->initialDownload = false;

        $transportData->pricesByCurrencyId = [
            $this->getReference(CurrencyDataFixture::CURRENCY_CZK)->getId() => Money::create('199.95'),
            $this->getReference(CurrencyDataFixture::CURRENCY_EUR)->getId() => Money::create('6.95'),
        ];
        $transportData->vat = $this->getReference(VatDataFixture::VAT_HIGH);
        $this->createTransport(self::TRANSPORT_PPL, $transportData);

        $transportData = $this->transportDataFactory->create();
        $transportData->name = [
            'cs' => 'Osobní převzetí',
            'sk' => 'Osobní převzetí',
            'de' => 'Personal collection',
        ];
        $transportData->balikobot = false;
        $transportData->balikobotShipper = null;
        $transportData->balikobotShipperService = null;

        $transportData->pricesByCurrencyId = [
            $this->getReference(CurrencyDataFixture::CURRENCY_CZK)->getId() => Money::zero(),
            $this->getReference(CurrencyDataFixture::CURRENCY_EUR)->getId() => Money::zero(),
        ];
        $transportData->description = [
            'cs' => 'Uvítá Vás milý personál!',
            'sk' => 'Uvítá Vás milý personál!',
            'de' => 'You will be welcomed by friendly staff!',
        ];
        $transportData->instructions = [
            'cs' => 'Těšíme se na Vaši návštěvu.',
            'sk' => 'Těšíme se na Vaši návštěvu.',
            'de' => 'We are looking forward to your visit.',
        ];
        $transportData->vat = $this->getReference(VatDataFixture::VAT_ZERO);
        $this->createTransport(self::TRANSPORT_PERSONAL, $transportData);
    }

    /**
     * @param string $referenceName
     * @param \Shopsys\FrameworkBundle\Model\Transport\TransportData $transportData
     */
    protected function createTransport($referenceName, TransportData $transportData)
    {
        /** @var \Shopsys\ShopBundle\Model\Transport\Transport $transport */
        $transport = $this->transportFacade->create($transportData);
        $transport->setAsDownloaded();
        $this->entityManager->flush($transport);

        $this->addReference($referenceName, $transport);
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            VatDataFixture::class,
            CurrencyDataFixture::class,
        ];
    }
}
