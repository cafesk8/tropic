<?php

namespace Shopsys\ShopBundle\DataFixtures\Demo;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Setting\Setting;

class SettingValueDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Setting\Setting
     */
    protected $setting;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Setting\Setting $setting
     */
    public function __construct(Setting $setting)
    {
        $this->setting = $setting;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $termsAndConditions = $this->getReference(ArticleDataFixture::ARTICLE_TERMS_AND_CONDITIONS_1);
        $privacyPolicy = $this->getReference(ArticleDataFixture::ARTICLE_PRIVACY_POLICY_1);
        /* @var $termsAndConditions \Shopsys\FrameworkBundle\Model\Article\Article */
        $cookies = $this->getReference(ArticleDataFixture::ARTICLE_COOKIES_1);
        /* @var $cookies \Shopsys\FrameworkBundle\Model\Article\Article */

        $personalDataDisplaySiteContent = 'Zadáním e-mailu níže si můžete nechat zobrazit vaše osobní údaje, která evidujeme v našem internetovém obchodu.
         Pro ověření vaší totožnosti vám po zadání e-mailové adresy bude zaslán e-mail s odkazem. 
         Klikem na odkaz se dostanete na stránku s přehledem těchto osobních údajů - půjde o všechny údaje evidované k dané e-mailové adrese.';

        $personalDataExportSiteContent = 'Zadáním e-mailu níže si můžete stáhnout své osobní a jiné informace (například historii objednávek)
         z našeho internetového obchodu. Pro ověření vaší totožnosti vám po zadání e-mailové adresy bude zaslán e-mail s odkazem.
         Klikem na odkaz se dostanete na stránku s s možností stažení těchto informací ve strojově čitelném formátu - půjde o údaje
         evidované k dané e-mailové adrese na této doméně internetového obchodu.';

        $this->setting->setForDomain(Setting::COOKIES_ARTICLE_ID, $cookies->getId(), Domain::FIRST_DOMAIN_ID);
        $this->setting->setForDomain(Setting::TERMS_AND_CONDITIONS_ARTICLE_ID, $termsAndConditions->getId(), Domain::FIRST_DOMAIN_ID);
        $this->setting->setForDomain(Setting::PRIVACY_POLICY_ARTICLE_ID, $privacyPolicy->getId(), Domain::FIRST_DOMAIN_ID);
        $this->setting->setForDomain(Setting::PERSONAL_DATA_DISPLAY_SITE_CONTENT, $personalDataDisplaySiteContent, Domain::FIRST_DOMAIN_ID);
        $this->setting->setForDomain(Setting::PERSONAL_DATA_EXPORT_SITE_CONTENT, $personalDataExportSiteContent, Domain::FIRST_DOMAIN_ID);
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            ArticleDataFixture::class,
            PricingGroupDataFixture::class,
        ];
    }
}
