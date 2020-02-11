<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Article;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade;
use Shopsys\FrameworkBundle\Model\Article\ArticleFacade as BaseArticleFacade;
use Shopsys\FrameworkBundle\Model\Article\ArticleFactoryInterface;
use Shopsys\FrameworkBundle\Model\Article\ArticleRepository;
use Shopsys\ShopBundle\Component\Setting\Setting;

/**
 * @property \Shopsys\ShopBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
 * @method \Shopsys\ShopBundle\Model\Article\Article|null findById(int $articleId)
 * @method \Shopsys\ShopBundle\Model\Article\Article getById(int $articleId)
 * @method \Shopsys\ShopBundle\Model\Article\Article getVisibleById(int $articleId)
 * @method \Shopsys\ShopBundle\Model\Article\Article[] getVisibleArticlesForPlacementOnCurrentDomain(string $placement)
 * @method \Shopsys\ShopBundle\Model\Article\Article create(\Shopsys\ShopBundle\Model\Article\ArticleData $articleData)
 * @method \Shopsys\ShopBundle\Model\Article\Article edit(int $articleId, \Shopsys\ShopBundle\Model\Article\ArticleData $articleData)
 * @method \Shopsys\ShopBundle\Model\Article\Article[] getAllByDomainId(int $domainId)
 */
class ArticleFacade extends BaseArticleFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Article\ArticleRepository
     */
    protected $articleRepository;

    /**
     * @var \Shopsys\ShopBundle\Component\Setting\Setting
     */
    private $setting;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\ShopBundle\Model\Article\ArticleRepository $articleRepository
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\ShopBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
     * @param \Shopsys\FrameworkBundle\Model\Article\ArticleFactoryInterface $articleFactory
     * @param \Shopsys\ShopBundle\Component\Setting\Setting $setting
     */
    public function __construct(
        EntityManagerInterface $em,
        ArticleRepository $articleRepository,
        Domain $domain,
        FriendlyUrlFacade $friendlyUrlFacade,
        ArticleFactoryInterface $articleFactory,
        Setting $setting
    ) {
        parent::__construct($em, $articleRepository, $domain, $friendlyUrlFacade, $articleFactory);

        $this->setting = $setting;
    }

    /**
     * @param string $placement
     * @param int $limit
     * @return \Shopsys\ShopBundle\Model\Article\Article[]
     */
    public function getVisibleArticlesOnCurrentDomainByPlacementAndLimit(string $placement, int $limit): array
    {
        return $this->articleRepository->getVisibleArticlesOnCurrentDomainByPlacementAndLimit($this->domain->getId(), $placement, $limit);
    }

    /**
     * @param string $settingValue
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Article\Article|null
     */
    public function findArticleBySettingValueAndDomainId(string $settingValue, int $domainId): ?Article
    {
        $articleId = $this->setting->getForDomain($settingValue, $domainId);

        if ($articleId !== null) {
            return $this->findById($articleId);
        }

        return null;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Article\Article|null $article
     * @param string $settingValue
     * @param int $domainId
     */
    public function setArticleOnDomainInSettings(?Article $article, string $settingValue, int $domainId): void
    {
        $articleId = null;
        if ($article !== null) {
            $articleId = $article->getId();
        }
        $this->setting->setForDomain(
            $settingValue,
            $articleId,
            $domainId
        );
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Article\Article $article
     * @return bool
     */
    public function isArticleUsedForLoyaltyProgram(Article $article): bool
    {
        foreach ($this->domain->getAll() as $domainConfig) {
            if ($this->findArticleBySettingValueAndDomainId(Setting::LOYALTY_PROGRAM_ARTICLE_ID, $domainConfig->getId()) === $article) {
                return true;
            }
        }

        return false;
    }
}
