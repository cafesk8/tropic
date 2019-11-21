<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\DataFixtures\Demo;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Transport\TransportData;
use Shopsys\FrameworkBundle\Model\Transport\TransportDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Transport\TransportFacade;
use Shopsys\ShopBundle\Model\Transport\Transport;

class TransportDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
{
    public const TRANSPORT_CZECH_POST = 'transport_cp';
    public const TRANSPORT_PPL = 'transport_ppl';
    public const TRANSPORT_PERSONAL = 'transport_personal';
    public const TRANSPORT_PPL_DE = 'transport_ppl_de';
    public const TRANSPORT_PPL_FR = 'transport_ppl_fr';

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
        $transportData->deliveryDays = 2;
        $transportData->pricesByCurrencyId = [
            $this->getReference(CurrencyDataFixture::CURRENCY_CZK)->getId() => Money::create('99.95'),
            $this->getReference(CurrencyDataFixture::CURRENCY_EUR)->getId() => Money::create('3.95'),
        ];
        $transportData->vat = $this->getReference(VatDataFixture::VAT_HIGH);
        $transportData->countries[] = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $transportData->countries[] = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);
        $this->createTransport(self::TRANSPORT_CZECH_POST, $transportData);

        $transportData = $this->transportDataFactory->create();
        $transportData->name = [
            'cs' => 'PPL',
            'sk' => 'PPL',
            'de' => 'PPL',
        ];
        $transportData->deliveryDays = 1;
        $transportData->transportType = Transport::TYPE_PERSONAL_TAKE_BALIKOBOT;
        $transportData->balikobotShipper = TransportPickupPlaceDataFixture::BALIKOBOT_SHIPPER;
        $transportData->balikobotShipperService = TransportPickupPlaceDataFixture::BALIKOBOT_SHIPPER_SERVICE;
        $transportData->initialDownload = false;

        $transportData->pricesByCurrencyId = [
            $this->getReference(CurrencyDataFixture::CURRENCY_CZK)->getId() => Money::create('199.95'),
            $this->getReference(CurrencyDataFixture::CURRENCY_EUR)->getId() => Money::create('6.95'),
        ];
        $transportData->vat = $this->getReference(VatDataFixture::VAT_HIGH);
        $transportData->countries[] = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $transportData->countries[] = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);
        $this->createTransport(self::TRANSPORT_PPL, $transportData);

        $transportData = $this->transportDataFactory->create();
        $transportData->name = [
            'cs' => 'Osobní převzetí',
            'sk' => 'Osobní převzetí',
            'de' => 'Personal collection',
        ];
        $transportData->deliveryDays = 1;
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
        $transportData->countries[] = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $transportData->countries[] = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);
        $this->createTransport(self::TRANSPORT_PERSONAL, $transportData);

        $transportData = $this->transportDataFactory->create();
        $transportData->name = [
            'cs' => 'PPL',
            'sk' => 'PPL',
            'de' => 'PPL',
        ];
        $transportData->deliveryDays = 3;
        $transportData->transportType = Transport::TYPE_PERSONAL_TAKE_BALIKOBOT;
        $transportData->balikobotShipper = TransportPickupPlaceDataFixture::BALIKOBOT_SHIPPER;
        $transportData->balikobotShipperService = TransportPickupPlaceDataFixture::BALIKOBOT_SHIPPER_SERVICE;
        $transportData->initialDownload = false;

        $transportData->pricesByCurrencyId = [
            $this->getReference(CurrencyDataFixture::CURRENCY_CZK)->getId() => Money::create('230.90'),
            $this->getReference(CurrencyDataFixture::CURRENCY_EUR)->getId() => Money::create('7.95'),
        ];
        $transportData->vat = $this->getReference(VatDataFixture::VAT_HIGH);
        $transportData->countries[] = $this->getReference(CountryDataFixture::COUNTRY_GERMANY);
        $this->createTransport(self::TRANSPORT_PPL_DE, $transportData);

        $transportData = $this->transportDataFactory->create();
        $transportData->name = [
            'cs' => 'PPL',
            'sk' => 'PPL',
            'de' => 'PPL',
        ];
        $transportData->deliveryDays = 1;
        $transportData->transportType = Transport::TYPE_PERSONAL_TAKE_BALIKOBOT;
        $transportData->balikobotShipper = TransportPickupPlaceDataFixture::BALIKOBOT_SHIPPER;
        $transportData->balikobotShipperService = TransportPickupPlaceDataFixture::BALIKOBOT_SHIPPER_SERVICE;
        $transportData->initialDownload = false;

        $transportData->pricesByCurrencyId = [
            $this->getReference(CurrencyDataFixture::CURRENCY_CZK)->getId() => Money::create('499.90'),
            $this->getReference(CurrencyDataFixture::CURRENCY_EUR)->getId() => Money::create('15.95'),
        ];
        $transportData->vat = $this->getReference(VatDataFixture::VAT_HIGH);
        $transportData->countries[] = $this->getReference(CountryDataFixture::COUNTRY_FRANCE);
        $this->createTransport(self::TRANSPORT_PPL_FR, $transportData);
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
            CountryDataFixture::class,
        ];
    }
}
