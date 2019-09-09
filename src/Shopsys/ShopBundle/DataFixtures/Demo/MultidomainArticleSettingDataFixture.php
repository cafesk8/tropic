<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\DataFixtures\Demo;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\ShopBundle\Component\Setting\Setting;
use Shopsys\ShopBundle\Model\Article\ArticleFacade;

class MultidomainArticleSettingDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
{
    /**
     * @var \Shopsys\ShopBundle\Model\Article\ArticleFacade
     */
    private $articleFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \Shopsys\ShopBundle\Model\Article\ArticleFacade $articleFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        ArticleFacade $articleFacade,
        Domain $domain
    ) {
        $this->articleFacade = $articleFacade;
        $this->domain = $domain;
    }

    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager): void
    {
        foreach ($this->domain->getAllIdsExcludingFirstDomain() as $domainId) {
            $this->loadForDomain($domainId);
        }
    }

    /**
     * @param int $domainId
     */
    protected function loadForDomain(int $domainId)
    {
        /** @var \Shopsys\ShopBundle\Model\Article\Article $bushmanClubArticle */
        $bushmanClubArticle = $this->getReferenceForDomain(MultidomainArticleDataFixture::ARTICLE_PRIVACY_POLICY, $domainId);
        /** @var \Shopsys\ShopBundle\Model\Article\Article $ourValuesArticle */
        $ourValuesArticle = $this->getReferenceForDomain(MultidomainArticleDataFixture::ARTICLE_PRIVACY_POLICY, $domainId);
        /** @var \Shopsys\ShopBundle\Model\Article\Article $ourStoryArticle */
        $ourStoryArticle = $this->getReferenceForDomain(MultidomainArticleDataFixture::ARTICLE_PRIVACY_POLICY, $domainId);
        /** @var \Shopsys\ShopBundle\Model\Article\Article $firstHeaderArticle */
        $firstHeaderArticle = $this->getReferenceForDomain(MultidomainArticleDataFixture::ARTICLE_TERMS_AND_CONDITIONS, $domainId);
        /** @var \Shopsys\ShopBundle\Model\Article\Article $secondHeaderArticle */
        $secondHeaderArticle = $this->getReferenceForDomain(MultidomainArticleDataFixture::ARTICLE_TERMS_AND_CONDITIONS, $domainId);
        /** @var \Shopsys\ShopBundle\Model\Article\Article $thirdHeaderArticle */
        $thirdHeaderArticle = $this->getReferenceForDomain(MultidomainArticleDataFixture::ARTICLE_TERMS_AND_CONDITIONS, $domainId);

        $this->articleFacade->setArticleOnDomainInSettings(
            $bushmanClubArticle,
            Setting::BUSHMAN_CLUB_ARTICLE_ID,
            $domainId
        );

        $this->articleFacade->setArticleOnDomainInSettings(
            $ourValuesArticle,
            Setting::OUR_VALUES_ARTICLE_ID,
            $domainId
        );

        $this->articleFacade->setArticleOnDomainInSettings(
            $ourStoryArticle,
            Setting::OUR_STORY_ARTICLE_ID,
            $domainId
        );

        $this->articleFacade->setArticleOnDomainInSettings(
            $firstHeaderArticle,
            Setting::FIRST_ARTICLE_ON_HEADER_MENU_ARTICLE_ID,
            $domainId
        );

        $this->articleFacade->setArticleOnDomainInSettings(
            $secondHeaderArticle,
            Setting::SECOND_ARTICLE_ON_HEADER_MENU_ARTICLE_ID,
            $domainId
        );

        $this->articleFacade->setArticleOnDomainInSettings(
            $thirdHeaderArticle,
            Setting::THIRD_ARTICLE_ON_HEADER_MENU_ARTICLE_ID,
            $domainId
        );
    }

    /**
     * @inheritDoc
     */
    public function getDependencies(): array
    {
        return [
            MultidomainArticleDataFixture::class,
        ];
    }
}
