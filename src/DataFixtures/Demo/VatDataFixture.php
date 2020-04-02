<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use App\Component\Domain\DomainHelper;
use App\Model\Pricing\Vat\Vat;
use App\Model\Pricing\Vat\VatData;
use App\Model\Pricing\Vat\VatFacade;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Setting\Setting;
use Shopsys\FrameworkBundle\Model\Pricing\Vat\VatDataFactoryInterface;

class VatDataFixture extends AbstractReferenceFixture
{
    public const VAT_ZERO = 'vat_zero';
    public const VAT_SECOND_LOW = 'vat_second_low';
    public const VAT_LOW = 'vat_low';
    public const VAT_HIGH = 'vat_high';
    public const VAT_RATES_BY_DOMAIN_ID = [
        DomainHelper::CZECH_DOMAIN => [
            self::VAT_ZERO => '0',
            self::VAT_SECOND_LOW => '10',
            self::VAT_LOW => '15',
            self::VAT_HIGH => '21',
        ],
        DomainHelper::SLOVAK_DOMAIN => [
            self::VAT_ZERO => '0',
            self::VAT_LOW => '10',
            self::VAT_HIGH => '20',
        ],
        DomainHelper::ENGLISH_DOMAIN => [
            self::VAT_ZERO => '0',
            self::VAT_SECOND_LOW => '10',
            self::VAT_LOW => '15',
            self::VAT_HIGH => '21',
        ],
    ];
    public const VAT_NAMES_BY_VAT_REF = [
        self::VAT_ZERO => 'Nulová sazba',
        self::VAT_SECOND_LOW => 'Druhá snížená sazba',
        self::VAT_LOW => 'Snížená sazba',
        self::VAT_HIGH => 'Základní sazba',
    ];
    public const VAT_POHODA_IDS_BY_VAT_REF = [
        self::VAT_ZERO => 0,
        self::VAT_SECOND_LOW => 3,
        self::VAT_LOW => 1,
        self::VAT_HIGH => 2,
    ];

    /**
     * @var \App\Model\Pricing\Vat\VatFacade
     */
    protected $vatFacade;

    /**
     * @var \App\Model\Pricing\Vat\VatDataFactory
     */
    protected $vatDataFactory;

    /**
     * @var \App\Component\Setting\Setting
     */
    protected $setting;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    protected $domain;

    /**
     * @param \App\Model\Pricing\Vat\VatFacade $vatFacade
     * @param \App\Model\Pricing\Vat\VatDataFactory $vatDataFactory
     * @param \App\Component\Setting\Setting $setting
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        VatFacade $vatFacade,
        VatDataFactoryInterface $vatDataFactory,
        Setting $setting,
        Domain $domain
    ) {
        $this->vatFacade = $vatFacade;
        $this->vatDataFactory = $vatDataFactory;
        $this->setting = $setting;
        $this->domain = $domain;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /**
         * Vat with zero rate is created in database migration.
         * @see \Shopsys\FrameworkBundle\Migrations\Version20180603135343
         */
        $vatZeroRate = $this->vatFacade->getById(1);
        $this->addReferenceForDomain(self::VAT_ZERO, $vatZeroRate, DomainHelper::CZECH_DOMAIN);
        $this->addReferenceForDomain(
            self::VAT_ZERO,
            $this->vatFacade->getDefaultVatForDomain(DomainHelper::SLOVAK_DOMAIN),
            DomainHelper::SLOVAK_DOMAIN
        );
        $this->addReferenceForDomain(
            self::VAT_ZERO,
            $this->vatFacade->getDefaultVatForDomain(DomainHelper::ENGLISH_DOMAIN),
            DomainHelper::ENGLISH_DOMAIN
        );

        /** @var \App\Model\Pricing\Vat\VatData $vatData */
        $vatData = $this->vatDataFactory->create();

        foreach ($this->domain->getAll() as $domainConfig) {
            $domainId = $domainConfig->getId();

            if (isset(self::VAT_RATES_BY_DOMAIN_ID[$domainId])) {
                foreach (self::VAT_RATES_BY_DOMAIN_ID[$domainId] as $vatRef => $vatRate) {
                    /** Zero rate VATs are created in DomainDataCreator */
                    if ($vatRef === self::VAT_ZERO) {
                        continue;
                    }

                    $vatData->name = self::VAT_NAMES_BY_VAT_REF[$vatRef];
                    $vatData->percent = $vatRate;
                    $vatData->pohodaId = $domainId === DomainHelper::CZECH_DOMAIN ? self::VAT_POHODA_IDS_BY_VAT_REF[$vatRef] : null;
                    $this->createVat($vatData, $domainId, $vatRef);
                }
            }

            $this->setHighVatAsDefault($domainId);
        }
    }

    /**
     * @param \App\Model\Pricing\Vat\VatData $vatData
     * @param int $domainId
     * @param string|null $referenceName
     */
    protected function createVat(VatData $vatData, int $domainId, $referenceName = null)
    {
        $vat = $this->vatFacade->create($vatData, $domainId);
        if ($referenceName !== null) {
            $this->addReferenceForDomain($referenceName, $vat, $domainId);
        }
    }

    /**
     * @param int $domainId
     */
    protected function setHighVatAsDefault(int $domainId): void
    {
        $defaultVat = $this->getReferenceForDomain(self::VAT_HIGH, $domainId);
        /** @var $defaultVat \App\Model\Pricing\Vat\Vat */
        $this->setting->setForDomain(Vat::SETTING_DEFAULT_VAT, $defaultVat->getId(), $domainId);
    }
}
