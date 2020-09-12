<?php

declare(strict_types=1);

namespace App\Model\Article;

use App\Component\Setting\Setting;
use App\Twig\Cache\TwigCacheFacade;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade;
use Shopsys\FrameworkBundle\Model\Article\ArticleData as BaseArticleData;
use Shopsys\FrameworkBundle\Model\Article\ArticleFacade as BaseArticleFacade;
use Shopsys\FrameworkBundle\Model\Article\ArticleFactoryInterface;
use Shopsys\FrameworkBundle\Model\Article\ArticleRepository;

/**
 * @property \App\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
 * @method \App\Model\Article\Article|null findById(int $articleId)
 * @method \App\Model\Article\Article getById(int $articleId)
 * @method \App\Model\Article\Article getVisibleById(int $articleId)
 * @method \App\Model\Article\Article[] getVisibleArticlesForPlacementOnCurrentDomain(string $placement)
 * @method \App\Model\Article\Article[] getAllByDomainId(int $domainId)
 */
class ArticleFacade extends BaseArticleFacade
{
    /**
     * @var \App\Model\Article\ArticleRepository
     */
    protected $articleRepository;

    /**
     * @var \App\Component\Setting\Setting
     */
    private $setting;

    private TwigCacheFacade $twigCacheFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Article\ArticleRepository $articleRepository
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
     * @param \Shopsys\FrameworkBundle\Model\Article\ArticleFactoryInterface $articleFactory
     * @param \App\Component\Setting\Setting $setting
     */
    public function __construct(
        EntityManagerInterface $em,
        ArticleRepository $articleRepository,
        Domain $domain,
        FriendlyUrlFacade $friendlyUrlFacade,
        ArticleFactoryInterface $articleFactory,
        Setting $setting,
        TwigCacheFacade $twigCacheFacade
    ) {
        parent::__construct($em, $articleRepository, $domain, $friendlyUrlFacade, $articleFactory);

        $this->setting = $setting;
        $this->twigCacheFacade = $twigCacheFacade;
    }

    /**
     * @param string $placement
     * @param int $limit
     * @return \App\Model\Article\Article[]
     */
    public function getVisibleArticlesOnCurrentDomainByPlacementAndLimit(string $placement, int $limit): array
    {
        return $this->articleRepository->getVisibleArticlesOnCurrentDomainByPlacementAndLimit($this->domain->getId(), $placement, $limit);
    }

    /**
     * @param string $settingValue
     * @param int $domainId
     * @return \App\Model\Article\Article|null
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
     * @param \App\Model\Article\Article|null $article
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
     * @param \App\Model\Article\Article $article
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

    /**
     * @param \App\Model\Article\ArticleData $articleData
     * @return \App\Model\Article\Article
     */
    public function create(BaseArticleData $articleData): Article
    {
        /** @var \App\Model\Article\Article $article */
        $article = parent::create($articleData);

        if (in_array($article->getPlacement(), $this->getCachedPlacements(), true)) {
            $this->twigCacheFacade->invalidateByKey($article->getPlacement(), $article->getDomainId());
        }

        return $article;
    }

    /**
     * @param int $articleId
     * @param \App\Model\Article\ArticleData $articleData
     * @return \App\Model\Article\Article
     */
    public function edit($articleId, BaseArticleData $articleData): Article
    {
        /** @var \App\Model\Article\Article $article */
        $article = parent::edit($articleId, $articleData);

        if (in_array($article->getPlacement(), $this->getCachedPlacements(), true)) {
            $this->twigCacheFacade->invalidateByKey($article->getPlacement(), $article->getDomainId());
        }

        return $article;
    }

    /**
     * @param int $articleId
     */
    public function delete($articleId): void
    {
        $article = $this->articleRepository->getById($articleId);

        $this->em->remove($article);
        $this->em->flush();

        if (in_array($article->getPlacement(), $this->getCachedPlacements(), true)) {
            $this->twigCacheFacade->invalidateByKey($article->getPlacement(), $article->getDomainId());
        }
    }

    /**
     * @return array
     */
    private function getCachedPlacements(): array
    {
        return [
            Article::PLACEMENT_SERVICES,
            Article::PLACEMENT_SERVICES,
            Article::PLACEMENT_TOP_MENU,
        ];
    }

}
