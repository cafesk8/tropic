<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use App\Component\MergadoTransportType\MergadoTransportTypeFacade;
use App\Model\Transport\Transport;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FrameworkBundle\Model\Pricing\PriceConverter;
use Shopsys\FrameworkBundle\Model\Transport\TransportData;
use Shopsys\FrameworkBundle\Model\Transport\TransportDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Transport\TransportFacade;

class TransportDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
{
    public const TRANSPORT_CZECH_POST = 'transport_cp';
    public const TRANSPORT_EMAIL = 'transport_email';
    public const TRANSPORT_PPL = 'transport_ppl';
    public const TRANSPORT_PERSONAL = 'transport_personal';
    public const TRANSPORT_PPL_DE = 'transport_ppl_de';
    public const TRANSPORT_PPL_FR = 'transport_ppl_fr';
    public const TRANSPORT_ZASILKOVNA_CZ = 'transport_zasilkovna_cz';
    public const TRANSPORT_ZASILKOVNA_SK = 'transport_zasilkovna_sk';

    /** @var \App\Model\Transport\TransportFacade */
    protected $transportFacade;

    /**
     * @var \App\Model\Transport\TransportDataFactory
     */
    protected $transportDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    protected $domain;

    /**
     * @var \App\Model\Pricing\Currency\CurrencyFacade
     */
    protected $currencyFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\PriceConverter
     */
    protected $priceConverter;

    /**
     * @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator
     */
    private $entityManager;

    /**
     * @param \App\Model\Transport\TransportFacade $transportFacade
     * @param \App\Model\Transport\TransportDataFactory $transportDataFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \Shopsys\FrameworkBundle\Model\Pricing\PriceConverter $priceConverter
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $entityManager
     */
    public function __construct(
        TransportFacade $transportFacade,
        TransportDataFactoryInterface $transportDataFactory,
        Domain $domain,
        CurrencyFacade $currencyFacade,
        PriceConverter $priceConverter,
        EntityManagerInterface $entityManager
    ) {
        $this->transportFacade = $transportFacade;
        $this->transportDataFactory = $transportDataFactory;
        $this->domain = $domain;
        $this->currencyFacade = $currencyFacade;
        $this->priceConverter = $priceConverter;
        $this->entityManager = $entityManager;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $transportData = $this->transportDataFactory->create();

        foreach ($this->domain->getAllLocales() as $locale) {
            $transportData->name[$locale] = t('??esk?? po??ta - bal??k do ruky', [], 'dataFixtures', $locale);
        }

        $transportData->mergadoTransportType = MergadoTransportTypeFacade::CZECH_POST;
        $transportData->zboziType = 'CESKA_POSTA';
        $this->setPriceForAllDomains($transportData, Money::create('99.95'));
        $transportData->countries[] = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $transportData->countries[] = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);
        $this->createTransport(self::TRANSPORT_CZECH_POST, $transportData);

        $transportData = $this->transportDataFactory->create();

        foreach ($this->domain->getAllLocales() as $locale) {
            $transportData->name[$locale] = t('PPL', [], 'dataFixtures', $locale);
        }
        $transportData->transportType = Transport::TYPE_PERSONAL_TAKE_BALIKOBOT;
        $transportData->balikobotShipper = TransportPickupPlaceDataFixture::BALIKOBOT_SHIPPER;
        $transportData->balikobotShipperService = TransportPickupPlaceDataFixture::BALIKOBOT_SHIPPER_SERVICE;
        $transportData->initialDownload = false;
        $transportData->mergadoTransportType = MergadoTransportTypeFacade::PPL;
        $transportData->zboziType = 'PPL';

        $this->setPriceForAllDomains($transportData, Money::create('199.95'));
        $transportData->countries[] = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $transportData->countries[] = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);
        $this->createTransport(self::TRANSPORT_PPL, $transportData);

        $transportData = $this->transportDataFactory->create();

        foreach ($this->domain->getAllLocales() as $locale) {
            $transportData->name[$locale] = t('Osobn?? p??evzet??', [], 'dataFixtures', $locale);
            $transportData->description[$locale] = t('Uv??t?? V??s mil?? person??l!', [], 'dataFixtures', $locale);
            $transportData->instructions[$locale] = t('T??????me se na Va??i n??v??t??vu.', [], 'dataFixtures', $locale);
        }
        $transportData->balikobotShipper = null;
        $transportData->balikobotShipperService = null;
        $transportData->mergadoTransportType = MergadoTransportTypeFacade::OWN_TRANSPORT;
        $transportData->zboziType = 'VLASTNI_VYDEJNI_MISTA';

        $this->setPriceForAllDomains($transportData, Money::zero());
        $transportData->countries[] = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $transportData->countries[] = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);
        $this->createTransport(self::TRANSPORT_PERSONAL, $transportData);

        $transportData = $this->transportDataFactory->create();
        foreach ($this->domain->getAllLocales() as $locale) {
            $transportData->name[$locale] = t('PPL - de', [], 'dataFixtures', $locale);
        }
        $transportData->transportType = Transport::TYPE_PERSONAL_TAKE_BALIKOBOT;
        $transportData->balikobotShipper = TransportPickupPlaceDataFixture::BALIKOBOT_SHIPPER;
        $transportData->balikobotShipperService = TransportPickupPlaceDataFixture::BALIKOBOT_SHIPPER_SERVICE;
        $transportData->initialDownload = false;
        $transportData->mergadoTransportType = MergadoTransportTypeFacade::PPL;
        $transportData->zboziType = 'PPL';
        $this->setPriceForAllDomains($transportData, Money::create('230.90'));
        $transportData->countries[] = $this->getReference(CountryDataFixture::COUNTRY_GERMANY);
        $this->createTransport(self::TRANSPORT_PPL_DE, $transportData);

        $transportData = $this->transportDataFactory->create();
        foreach ($this->domain->getAllLocales() as $locale) {
            $transportData->name[$locale] = t('PPL - fr', [], 'dataFixtures', $locale);
        }
        $transportData->transportType = Transport::TYPE_PERSONAL_TAKE_BALIKOBOT;
        $transportData->balikobotShipper = TransportPickupPlaceDataFixture::BALIKOBOT_SHIPPER;
        $transportData->balikobotShipperService = TransportPickupPlaceDataFixture::BALIKOBOT_SHIPPER_SERVICE;
        $transportData->mergadoTransportType = MergadoTransportTypeFacade::PPL;
        $transportData->zboziType = 'PPL';
        $transportData->initialDownload = false;
        $this->setPriceForAllDomains($transportData, Money::create('499.90'));
        $transportData->countries[] = $this->getReference(CountryDataFixture::COUNTRY_FRANCE);
        $this->createTransport(self::TRANSPORT_PPL_FR, $transportData);

        $transportData = $this->transportDataFactory->create();

        foreach ($this->domain->getAllLocales() as $locale) {
            $transportData->name[$locale] = t('Z??silkovna CZ', [], 'dataFixtures', $locale);
        }
        $transportData->transportType = Transport::TYPE_ZASILKOVNA_CZ;
        $transportData->mergadoTransportType = MergadoTransportTypeFacade::ZASILKOVNA;
        $transportData->initialDownload = false;

        $this->setPriceForAllDomains($transportData, Money::create('68'));
        $transportData->countries[] = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $this->createTransport(self::TRANSPORT_ZASILKOVNA_CZ, $transportData);

        $transportData = $this->transportDataFactory->create();

        foreach ($this->domain->getAllLocales() as $locale) {
            $transportData->name[$locale] = t('Z??silkovna SK', [], 'dataFixtures', $locale);
        }
        $transportData->transportType = Transport::TYPE_ZASILKOVNA_SK;
        $transportData->mergadoTransportType = MergadoTransportTypeFacade::ZASILKOVNA;
        $transportData->zboziType = 'ZASILKOVNA';
        $transportData->initialDownload = false;

        $this->setPriceForAllDomains($transportData, Money::create('3'));
        $transportData->countries[] = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);
        $this->createTransport(self::TRANSPORT_ZASILKOVNA_SK, $transportData);

        $transportData = $this->transportDataFactory->create();

        foreach ($this->domain->getAllLocales() as $locale) {
            $transportData->name[$locale] = t('Emailem', [], 'dataFixtures', $locale);
        }

        $transportData->transportType = Transport::TYPE_EMAIL;
        $transportData->zboziType = 'VLASTNI_PREPRAVA';
        $this->setPriceForAllDomains($transportData, Money::zero());
        $transportData->countries[] = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $transportData->countries[] = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);

        $this->createTransport(self::TRANSPORT_EMAIL, $transportData);
    }

    /**
     * @param string $referenceName
     * @param \App\Model\Transport\TransportData $transportData
     */
    protected function createTransport($referenceName, TransportData $transportData)
    {
        $transport = $this->transportFacade->create($transportData);
        $transport->setAsDownloaded();
        $this->entityManager->flush($transport);

        $this->addReference($referenceName, $transport);
    }

    /**
     * @param \App\Model\Transport\TransportData $transportData
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $price
     */
    protected function setPriceForAllDomains(TransportData $transportData, Money $price): void
    {
        foreach ($this->domain->getAllIncludingDomainConfigsWithoutDataCreated() as $domain) {
            $price = $this->priceConverter->convertPriceWithoutVatToPriceInDomainDefaultCurrency($price, $domain->getId());

            /** @var \App\Model\Pricing\Vat\Vat $vat */
            $vat = $this->getReferenceForDomain(VatDataFixture::VAT_HIGH, $domain->getId());
            $transportData->vatsIndexedByDomainId[$domain->getId()] = $vat;
            $transportData->pricesIndexedByDomainId[$domain->getId()] = $price;
            $transportData->actionPricesIndexedByDomainId[$domain->getId()] = Money::create('0');
            $transportData->minActionOrderPricesIndexedByDomainId[$domain->getId()] = Money::create('100000');
            $transportData->actionDatesFromIndexedByDomainId[$domain->getId()] = null;
            $transportData->actionDatesToIndexedByDomainId[$domain->getId()] = null;
            $transportData->actionActiveIndexedByDomainId[$domain->getId()] = true;
            $transportData->minFreeOrderPricesIndexedByDomainId[$domain->getId()] = null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            VatDataFixture::class,
            CurrencyDataFixture::class,
            SettingValueDataFixture::class,
        ];
    }
}
