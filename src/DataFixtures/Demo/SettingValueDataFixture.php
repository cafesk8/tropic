<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Setting\Setting;
use Shopsys\FrameworkBundle\Model\ContactForm\ContactFormSettingsDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\ContactForm\ContactFormSettingsFacade;
use Shopsys\FrameworkBundle\Model\Pricing\PricingSetting;
use Shopsys\FrameworkBundle\Model\Seo\SeoSettingFacade;

class SettingValueDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
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
     * @var \Shopsys\FrameworkBundle\Model\ContactForm\ContactFormSettingsFacade
     */
    private $contactFormSettingsFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\ContactForm\ContactFormSettingsDataFactoryInterface
     */
    private $contactFormSettingsDataFactory;

    /**
     * @param \App\Component\Setting\Setting $setting
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\ContactForm\ContactFormSettingsFacade $contactFormSettingsFacade
     * @param \Shopsys\FrameworkBundle\Model\ContactForm\ContactFormSettingsDataFactoryInterface $contactFormSettingsDataFactory
     */
    public function __construct(
        Setting $setting,
        Domain $domain,
        ContactFormSettingsFacade $contactFormSettingsFacade,
        ContactFormSettingsDataFactoryInterface $contactFormSettingsDataFactory
    ) {
        $this->setting = $setting;
        $this->domain = $domain;
        $this->contactFormSettingsFacade = $contactFormSettingsFacade;
        $this->contactFormSettingsDataFactory = $contactFormSettingsDataFactory;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->domain->getAll() as $domainConfig) {
            $domainId = $domainConfig->getId();
            $locale = $domainConfig->getLocale();

            /** @var \App\Model\Article\Article $termsAndConditions */
            $termsAndConditions = $this->getReferenceForDomain(ArticleDataFixture::ARTICLE_TERMS_AND_CONDITIONS, $domainId);
            $this->setting->setForDomain(Setting::TERMS_AND_CONDITIONS_ARTICLE_ID, $termsAndConditions->getId(), $domainId);

            /** @var \App\Model\Article\Article $privacyPolicy */
            $privacyPolicy = $this->getReferenceForDomain(ArticleDataFixture::ARTICLE_PRIVACY_POLICY, $domainId);
            $this->setting->setForDomain(Setting::PRIVACY_POLICY_ARTICLE_ID, $privacyPolicy->getId(), $domainId);

            /** @var \App\Model\Article\Article $cookies */
            $cookies = $this->getReferenceForDomain(ArticleDataFixture::ARTICLE_COOKIES, $domainId);
            $this->setting->setForDomain(Setting::COOKIES_ARTICLE_ID, $cookies->getId(), $domainId);

            $personalDataDisplaySiteContent = t('Zad??n??m e-mailu n????e si m????ete nechat zobrazit va??e osobn?? ??daje, kter?? evidujeme v na??em internetov??m obchodu.
             Pro ov????en?? va???? toto??nosti v??m po zad??n?? e-mailov?? adresy bude zasl??n e-mail s odkazem. 
             Klikem na odkaz se dostanete na str??nku s p??ehledem t??chto osobn??ch ??daj?? - p??jde o v??echny ??daje evidovan?? k dan?? e-mailov?? adrese.', [], 'dataFixtures', $locale);
            $this->setting->setForDomain(Setting::PERSONAL_DATA_DISPLAY_SITE_CONTENT, $personalDataDisplaySiteContent, $domainId);

            $personalDataExportSiteContent = t('Zad??n??m e-mailu n????e si m????ete st??hnout sv?? osobn?? a jin?? informace (nap????klad historii objedn??vek)
         z na??eho internetov??ho obchodu. Pro ov????en?? va???? toto??nosti v??m po zad??n?? e-mailov?? adresy bude zasl??n e-mail s odkazem.
         Klikem na odkaz se dostanete na str??nku s s mo??nost?? sta??en?? t??chto informac?? ve strojov?? ??iteln??m form??tu - p??jde o ??daje
         evidovan?? k dan?? e-mailov?? adrese na t??to dom??n?? internetov??ho obchodu.', [], 'dataFixtures', $locale);
            $this->setting->setForDomain(Setting::PERSONAL_DATA_EXPORT_SITE_CONTENT, $personalDataExportSiteContent, $domainId);

            $orderSentText = t('
                <p>
                    Objedn??vka ????slo {number} byla odesl??na, d??kujeme za V???? n??kup.
                    Budeme V??s kontaktovat o dal????m pr??b??hu vy??izov??n??. <br /><br />
                    Uschovejte si permanentn?? <a href="{order_detail_url}">odkaz na detail objedn??vky</a>. <br />
                    {transport_instructions} <br />
                    {payment_instructions} <br />
                </p>
            ', [], 'dataFixtures', $locale);
            $this->setting->setForDomain(Setting::ORDER_SENT_PAGE_CONTENT, $orderSentText, $domainId);

            /** @var \App\Model\Pricing\Group\PricingGroup $pricingGroup */
            $pricingGroup = $this->getReferenceForDomain(PricingGroupDataFixture::PRICING_GROUP_BASIC_DOMAIN, $domainId);
            $this->setting->setForDomain(Setting::DEFAULT_PRICING_GROUP, $pricingGroup->getId(), $domainId);

            $this->setting->setForDomain(
                SeoSettingFacade::SEO_META_DESCRIPTION_MAIN_PAGE,
                t('Shopsys Framework - nejlep???? ??e??en?? pro v???? internetov?? obchod.', [], 'dataFixtures', $locale),
                $domainId
            );
            $this->setting->setForDomain(SeoSettingFacade::SEO_TITLE_MAIN_PAGE, t('Shopsys Framework - Tituln?? strana', [], 'dataFixtures', $locale), $domainId);
            $this->setting->setForDomain(SeoSettingFacade::SEO_TITLE_ADD_ON, t('| Demo obchod', [], 'dataFixtures', $locale), $domainId);

            /** @var \App\Model\Article\Article $articleProductSizeDomain */
            $articleProductSizeDomain = $this->getReferenceForDomain(ArticleDataFixture::ARTICLE_PRODUCT_SIZE, $domainId);
            $this->setting->setForDomain(\App\Component\Setting\Setting::PRODUCT_SIZE_ARTICLE_ID, $articleProductSizeDomain->getId(), $domainId);

            $this->setContactFormMainText($domainId, $locale);

            $this->setDomainDefaultCurrency($domainId);
        }
    }

    /**
     * @param int $domainId
     */
    protected function setDomainDefaultCurrency(int $domainId): void
    {
        if ($domainId === Domain::FIRST_DOMAIN_ID) {
            /** @var \App\Model\Pricing\Currency\Currency $defaultCurrency */
            $defaultCurrency = $this->getReference(CurrencyDataFixture::CURRENCY_CZK);
        } else {
            /** @var \App\Model\Pricing\Currency\Currency $defaultCurrency */
            $defaultCurrency = $this->getReference(CurrencyDataFixture::CURRENCY_EUR);
        }
        $this->setting->setForDomain(PricingSetting::DEFAULT_DOMAIN_CURRENCY, $defaultCurrency->getId(), $domainId);
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            ArticleDataFixture::class,
            PricingGroupDataFixture::class,
            CurrencyDataFixture::class,
        ];
    }

    /**
     * @param int $domainId
     * @param string $locale
     */
    protected function setContactFormMainText(int $domainId, string $locale): void
    {
        $contactFormSettingData = $this->contactFormSettingsDataFactory->createFromSettingsByDomainId($domainId);
        $contactFormMainText = t('M??te dotaz?', [], 'dataFixtures', $locale);
        $contactFormSettingData->mainText = $contactFormMainText;
        $this->contactFormSettingsFacade->editSettingsForDomain($contactFormSettingData, $domainId);
    }
}
