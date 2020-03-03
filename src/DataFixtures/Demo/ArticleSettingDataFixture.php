<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use App\Component\Setting\Setting;
use App\Model\Article\ArticleFacade;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;

class ArticleSettingDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
{
    /**
     * @var \App\Model\Article\ArticleFacade
     */
    private $articleFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \App\Model\Article\ArticleFacade $articleFacade
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

            /** @var \App\Model\Article\Article $termsAndConditionsArticle */
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
                /** @var \App\Model\Article\Article $loyaltyProgramArticle */
                $loyaltyProgramArticle = $this->getReferenceForDomain(ArticleDataFixture::ARTICLE_PRIVACY_POLICY, $domainId);
                $this->articleFacade->setArticleOnDomainInSettings(
                    $loyaltyProgramArticle,
                    Setting::LOYALTY_PROGRAM_ARTICLE_ID,
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
