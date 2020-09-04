<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use App\Model\Product\Flag\Flag;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Product\Flag\FlagData;
use Shopsys\FrameworkBundle\Model\Product\Flag\FlagDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\Flag\FlagFacade;

class FlagDataFixture extends AbstractReferenceFixture
{
    public const FLAG_NEW_PRODUCT = 'flag_new_product';
    public const FLAG_TOP_PRODUCT = 'flag_top_product';
    public const FLAG_ACTION_PRODUCT = 'flag_action';
    public const FLAG_SALE_PRODUCT = 'flag_sale';
    public const FLAG_DISCOUNT_PRODUCT = 'flag_discount';
    public const FLAG_CLEARANCE_PRODUCT = 'flag_clearance';
    public const FLAG_RECOMMENDED_PRODUCT = 'flag_recommended';
    public const FLAG_PREPARATION_PRODUCT = 'flag_preparation';

    /**
     * @var \App\Model\Product\Flag\FlagFacade
     */
    protected $flagFacade;

    /**
     * @var \App\Model\Product\Flag\FlagDataFactory
     */
    protected $flagDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    protected $domain;

    /**
     * @param \App\Model\Product\Flag\FlagFacade $flagFacade
     * @param \App\Model\Product\Flag\FlagDataFactory $flagDataFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        FlagFacade $flagFacade,
        FlagDataFactoryInterface $flagDataFactory,
        Domain $domain
    ) {
        $this->flagFacade = $flagFacade;
        $this->flagDataFactory = $flagDataFactory;
        $this->domain = $domain;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $flagData = $this->flagDataFactory->create();

        foreach ($this->domain->getAllLocales() as $locale) {
            $flagData->name[$locale] = t('Novinka', [], 'dataFixtures', $locale);
        }

        $flagData->pohodaId = Flag::POHODA_ID_NEW;
        $flagData->rgbColor = '#efd6ff';
        $flagData->visible = true;
        $flagData->news = true;
        $this->createFlag($flagData, self::FLAG_NEW_PRODUCT);

        foreach ($this->domain->getAllLocales() as $locale) {
            $flagData->name[$locale] = t('Nejprodávanější', [], 'dataFixtures', $locale);
        }

        $flagData->pohodaId = null;
        $flagData->rgbColor = '#d6fffa';
        $flagData->visible = true;
        $flagData->news = false;
        $this->createFlag($flagData, self::FLAG_TOP_PRODUCT);

        foreach ($this->domain->getAllLocales() as $locale) {
            $flagData->name[$locale] = t('Akce', [], 'dataFixtures', $locale);
        }

        $flagData->rgbColor = '#f9ffd6';
        $flagData->visible = true;
        $flagData->pohodaId = Flag::POHODA_ID_ACTION;
        $this->createFlag($flagData, self::FLAG_ACTION_PRODUCT);

        foreach ($this->domain->getAllLocales() as $locale) {
            $flagData->name[$locale] = t('Sleva', [], 'dataFixtures', $locale);
        }

        $flagData->rgbColor = '#000000';
        $flagData->visible = true;
        $flagData->pohodaId = Flag::POHODA_ID_DISCOUNT;
        $flagData->sale = false;
        $this->createFlag($flagData, self::FLAG_DISCOUNT_PRODUCT);

        foreach ($this->domain->getAllLocales() as $locale) {
            $flagData->name[$locale] = t('Připravujeme', [], 'dataFixtures', $locale);
        }

        $flagData->rgbColor = '#000000';
        $flagData->visible = true;
        $flagData->pohodaId = Flag::POHODA_ID_PREPARATION;
        $this->createFlag($flagData, self::FLAG_PREPARATION_PRODUCT);

        foreach ($this->domain->getAllLocales() as $locale) {
            $flagData->name[$locale] = t('Tropic doporučuje', [], 'dataFixtures', $locale);
        }

        $flagData->rgbColor = '#000000';
        $flagData->visible = true;
        $flagData->pohodaId = Flag::POHODA_ID_RECOMMENDED;
        $this->createFlag($flagData, self::FLAG_RECOMMENDED_PRODUCT);

        foreach ($this->domain->getAllLocales() as $locale) {
            $flagData->name[$locale] = t('Doprodej', [], 'dataFixtures', $locale);
        }

        $flagData->rgbColor = '#000000';
        $flagData->visible = true;
        $flagData->pohodaId = Flag::POHODA_ID_CLEARANCE;
        $this->createFlag($flagData, self::FLAG_CLEARANCE_PRODUCT);
        $this->createSaleFlagReference();
    }

    /**
     * @param \App\Model\Product\Flag\FlagData $flagData
     * @param string|null $referenceName
     */
    protected function createFlag(FlagData $flagData, $referenceName = null)
    {
        $flags = $this->flagFacade->getAllIndexedByPohodaId();

        if (isset($flags[$flagData->pohodaId])) {
            $flag = $this->flagFacade->edit($flags[$flagData->pohodaId]->getId(), $flagData);
        } else {
            $flag = $this->flagFacade->create($flagData);
        }

        if ($referenceName !== null) {
            $this->addReference($referenceName, $flag);
        }
    }

    /**
     * The sale flag is created in the database migration, @see \App\Migrations\Version20200511164719
     */
    private function createSaleFlagReference(): void
    {
        $saleFlags = $this->flagFacade->getSaleFlags();
        $saleFlag = reset($saleFlags);
        $saleFlagData = $this->flagDataFactory->createFromFlag($saleFlag);
        foreach ($this->domain->getAllLocales() as $locale) {
            $saleFlagData->name[$locale] = t('Výprodej', [], 'dataFixtures', $locale);
        }
        $this->flagFacade->edit($saleFlag->getId(), $saleFlagData);
        $this->addReference(self::FLAG_SALE_PRODUCT, $saleFlag);
    }
}
