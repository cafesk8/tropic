<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

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

        $flagData->rgbColor = '#efd6ff';
        $flagData->visible = true;
        $this->createFlag($flagData, self::FLAG_NEW_PRODUCT);

        foreach ($this->domain->getAllLocales() as $locale) {
            $flagData->name[$locale] = t('Nejprodávanější', [], 'dataFixtures', $locale);
        }

        $flagData->rgbColor = '#d6fffa';
        $flagData->visible = true;
        $this->createFlag($flagData, self::FLAG_TOP_PRODUCT);

        foreach ($this->domain->getAllLocales() as $locale) {
            $flagData->name[$locale] = t('Akce', [], 'dataFixtures', $locale);
        }

        $flagData->rgbColor = '#f9ffd6';
        $flagData->visible = true;
        $flagData->sale = true;
        $this->createFlag($flagData, self::FLAG_ACTION_PRODUCT);
    }

    /**
     * @param \App\Model\Product\Flag\FlagData $flagData
     * @param string|null $referenceName
     */
    protected function createFlag(FlagData $flagData, $referenceName = null)
    {
        $flag = $this->flagFacade->create($flagData);
        if ($referenceName !== null) {
            $this->addReference($referenceName, $flag);
        }
    }
}
