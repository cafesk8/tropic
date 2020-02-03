<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Setting\Setting;
use Shopsys\FrameworkBundle\Model\ShopInfo\ShopInfoSettingFacade;

class SettingValueShopInfoDataFixture extends AbstractReferenceFixture
{
    /**
     * @var \App\Component\Setting\Setting
     */
    protected $setting;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    protected $domain;

    /**
     * @param \App\Component\Setting\Setting $setting
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(Setting $setting, Domain $domain)
    {
        $this->setting = $setting;
        $this->domain = $domain;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->domain->getAll() as $domainConfig) {
            $domainId = $domainConfig->getId();
            $locale = $domainConfig->getLocale();
            $this->setting->setForDomain(ShopInfoSettingFacade::SHOP_INFO_PHONE_NUMBER, t('+420 595 177 177', [], 'dataFixtures', $locale), $domainId);
            $this->setting->setForDomain(ShopInfoSettingFacade::SHOP_INFO_PHONE_HOURS, t('(Po - Pá: 8:00 - 16:00)', [], 'dataFixtures', $locale), $domainId);
            $this->setting->setForDomain(ShopInfoSettingFacade::SHOP_INFO_EMAIL, t('info@shopsys.com', [], 'dataFixtures', $locale), $domainId);
        }
    }
}
