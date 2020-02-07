<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\DataFixtures\Demo;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FrameworkBundle\Model\Pricing\PriceConverter;
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
     * @var \Shopsys\ShopBundle\Model\Transport\TransportDataFactory
     */
    protected $transportDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    protected $domain;

    /**
     * @var \Shopsys\ShopBundle\Model\Pricing\Currency\CurrencyFacade
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
     * @param \Shopsys\ShopBundle\Model\Transport\TransportFacade $transportFacade
     * @param \Shopsys\ShopBundle\Model\Transport\TransportDataFactory $transportDataFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\ShopBundle\Model\Pricing\Currency\CurrencyFacade $currencyFacade
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
            $transportData->name[$locale] = t('Česká pošta - balík do ruky', [], 'dataFixtures', $locale);
        }

        $transportData->deliveryDays = 2;
        $this->setPriceForAllDomainDefaultCurrencies($transportData, Money::create('99.95'));

        $transportData->vat = $this->getReference(VatDataFixture::VAT_HIGH);
        $transportData->countries[] = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $transportData->countries[] = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);
        $this->createTransport(self::TRANSPORT_CZECH_POST, $transportData);

        $transportData = $this->transportDataFactory->create();

        foreach ($this->domain->getAllLocales() as $locale) {
            $transportData->name[$locale] = t('PPL', [], 'dataFixtures', $locale);
        }
        $transportData->deliveryDays = 1;
        $transportData->transportType = Transport::TYPE_PERSONAL_TAKE_BALIKOBOT;
        $transportData->balikobotShipper = TransportPickupPlaceDataFixture::BALIKOBOT_SHIPPER;
        $transportData->balikobotShipperService = TransportPickupPlaceDataFixture::BALIKOBOT_SHIPPER_SERVICE;
        $transportData->initialDownload = false;

        $this->setPriceForAllDomainDefaultCurrencies($transportData, Money::create('199.95'));

        $transportData->vat = $this->getReference(VatDataFixture::VAT_HIGH);
        $transportData->countries[] = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $transportData->countries[] = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);
        $this->createTransport(self::TRANSPORT_PPL, $transportData);

        $transportData = $this->transportDataFactory->create();

        foreach ($this->domain->getAllLocales() as $locale) {
            $transportData->name[$locale] = t('Osobní převzetí', [], 'dataFixtures', $locale);
            $transportData->description[$locale] = t('Uvítá Vás milý personál!', [], 'dataFixtures', $locale);
            $transportData->instructions[$locale] = t('Těšíme se na Vaši návštěvu.', [], 'dataFixtures', $locale);
        }
        $transportData->deliveryDays = 1;
        $transportData->balikobotShipper = null;
        $transportData->balikobotShipperService = null;

        $this->setPriceForAllDomainDefaultCurrencies($transportData, Money::zero());

        $transportData->vat = $this->getReference(VatDataFixture::VAT_ZERO);
        $transportData->countries[] = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $transportData->countries[] = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);
        $this->createTransport(self::TRANSPORT_PERSONAL, $transportData);

        $transportData = $this->transportDataFactory->create();
        foreach ($this->domain->getAllLocales() as $locale) {
            $transportData->name[$locale] = t('PPL - de', [], 'dataFixtures', $locale);
        }
        $transportData->deliveryDays = 3;
        $transportData->transportType = Transport::TYPE_PERSONAL_TAKE_BALIKOBOT;
        $transportData->balikobotShipper = TransportPickupPlaceDataFixture::BALIKOBOT_SHIPPER;
        $transportData->balikobotShipperService = TransportPickupPlaceDataFixture::BALIKOBOT_SHIPPER_SERVICE;
        $transportData->initialDownload = false;
        $this->setPriceForAllDomainDefaultCurrencies($transportData, Money::create('230.90'));
        $transportData->vat = $this->getReference(VatDataFixture::VAT_HIGH);
        $transportData->countries[] = $this->getReference(CountryDataFixture::COUNTRY_GERMANY);
        $this->createTransport(self::TRANSPORT_PPL_DE, $transportData);

        $transportData = $this->transportDataFactory->create();
        foreach ($this->domain->getAllLocales() as $locale) {
            $transportData->name[$locale] = t('PPL - fr', [], 'dataFixtures', $locale);
        }
        $transportData->deliveryDays = 1;
        $transportData->transportType = Transport::TYPE_PERSONAL_TAKE_BALIKOBOT;
        $transportData->balikobotShipper = TransportPickupPlaceDataFixture::BALIKOBOT_SHIPPER;
        $transportData->balikobotShipperService = TransportPickupPlaceDataFixture::BALIKOBOT_SHIPPER_SERVICE;
        $transportData->initialDownload = false;
        $this->setPriceForAllDomainDefaultCurrencies($transportData, Money::create('499.90'));
        $transportData->vat = $this->getReference(VatDataFixture::VAT_HIGH);
        $transportData->countries[] = $this->getReference(CountryDataFixture::COUNTRY_FRANCE);
        $this->createTransport(self::TRANSPORT_PPL_FR, $transportData);
    }

    /**
     * @param string $referenceName
     * @param \Shopsys\ShopBundle\Model\Transport\TransportData $transportData
     */
    protected function createTransport($referenceName, TransportData $transportData)
    {
        $transport = $this->transportFacade->create($transportData);
        $transport->setAsDownloaded();
        $this->entityManager->flush($transport);

        $this->addReference($referenceName, $transport);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Transport\TransportData $transportData
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $price
     */
    protected function setPriceForAllDomainDefaultCurrencies(TransportData $transportData, Money $price): void
    {
        foreach ($this->domain->getAllIncludingDomainConfigsWithoutDataCreated() as $domain) {
            $currency = $this->currencyFacade->getDomainDefaultCurrencyByDomainId($domain->getId());
            $price = $this->priceConverter->convertPriceWithoutVatToPriceInDomainDefaultCurrency($price, $domain->getId());

            $transportData->pricesByCurrencyId[$currency->getId()] = $price;
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
