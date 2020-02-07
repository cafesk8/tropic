<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\DataFixtures\Demo;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\ShopBundle\Component\Setting\Setting;
use Shopsys\ShopBundle\Model\Article\ArticleFacade;

class ArticleSettingDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
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
        foreach ($this->domain->getAll() as $domainConfig) {
            $domainId = $domainConfig->getId();

            /** @var \Shopsys\ShopBundle\Model\Article\Article $termsAndConditionsArticle */
            $termsAndConditionsArticle = $this->getReferenceForDomain(ArticleDataFixture::ARTICLE_TERMS_AND_CONDITIONS, $domainId);

            $this->articleFacade->setArticleOnDomainInSettings(
                $termsAndConditionsArticle,
                Setting::FIRST_ARTICLE_ON_HEADER_MENU_ARTICLE_ID,
                $domainId
            );

            $this->articleFacade->setArticleOnDomainInSettings(
                $termsAndConditionsArticle,
                Setting::SECOND_ARTICLE_ON_HEADER_MENU_ARTICLE_ID,
                $domainId
            );

            $this->articleFacade->setArticleOnDomainInSettings(
                $termsAndConditionsArticle,
                Setting::THIRD_ARTICLE_ON_HEADER_MENU_ARTICLE_ID,
                $domainId
            );

            if ($domainId !== Domain::FIRST_DOMAIN_ID) {
                /** @var \Shopsys\ShopBundle\Model\Article\Article $bushmanClubArticle */
                $bushmanClubArticle = $this->getReferenceForDomain(ArticleDataFixture::ARTICLE_PRIVACY_POLICY, $domainId);
                /** @var \Shopsys\ShopBundle\Model\Article\Article $ourValuesArticle */
                $ourValuesArticle = $this->getReferenceForDomain(ArticleDataFixture::ARTICLE_PRIVACY_POLICY, $domainId);
                /** @var \Shopsys\ShopBundle\Model\Article\Article $ourStoryArticle */
                $ourStoryArticle = $this->getReferenceForDomain(ArticleDataFixture::ARTICLE_PRIVACY_POLICY, $domainId);
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
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getDependencies(): array
    {
        return [
            ArticleDataFixture::class,
        ];
    }
}
