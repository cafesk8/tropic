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
     * @param \Shopsys\ShopBundle\Model\Article\ArticleFacade $articleFacade
     */
    public function __construct(
        ArticleFacade $articleFacade
    ) {
        $this->articleFacade = $articleFacade;
    }

    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager): void
    {
        /** @var \Shopsys\ShopBundle\Model\Article\Article $firstHeaderArticle */
        $firstHeaderArticle = $this->getReference(ArticleDataFixture::ARTICLE_TERMS_AND_CONDITIONS_1);
        /** @var \Shopsys\ShopBundle\Model\Article\Article $secondHeaderArticle */
        $secondHeaderArticle = $this->getReference(ArticleDataFixture::ARTICLE_TERMS_AND_CONDITIONS_1);

        $this->articleFacade->setArticleOnDomainInSettings(
            $firstHeaderArticle,
            Setting::FIRST_ARTICLE_ON_HEADER_MENU_ARTICLE_ID,
            Domain::FIRST_DOMAIN_ID
        );

        $this->articleFacade->setArticleOnDomainInSettings(
            $secondHeaderArticle,
            Setting::SECOND_ARTICLE_ON_HEADER_MENU_ARTICLE_ID,
            Domain::FIRST_DOMAIN_ID
        );
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
