<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Blog\Article;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade;

class BlogArticleDataFactory
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade
     */
    private $friendlyUrlFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        FriendlyUrlFacade $friendlyUrlFacade,
        Domain $domain
    ) {
        $this->friendlyUrlFacade = $friendlyUrlFacade;
        $this->domain = $domain;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle $blogArticle
     * @return \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleData
     */
    public function createFromBlogArticle(BlogArticle $blogArticle): BlogArticleData
    {
        $blogArticleData = new BlogArticleData();
        $this->fillFromBlogArticle($blogArticleData, $blogArticle);

        return $blogArticleData;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleData
     */
    public function create(): BlogArticleData
    {
        $blogArticleData = new BlogArticleData();
        $this->fillNew($blogArticleData);

        return $blogArticleData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleData $blogArticleData
     */
    private function fillNew(BlogArticleData $blogArticleData): void
    {
        foreach ($this->domain->getAllIds() as $domainId) {
            $blogArticleData->seoMetaDescriptions[$domainId] = null;
            $blogArticleData->seoTitles[$domainId] = null;
            $blogArticleData->seoH1s[$domainId] = null;
            $blogArticleData->enabled[$domainId] = true;
        }

        foreach ($this->domain->getAllLocales() as $locale) {
            $blogArticleData->names[$locale] = null;
            $blogArticleData->descriptions[$locale] = null;
            $blogArticleData->perexes[$locale] = null;
        }
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleData $blogArticleData
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle $blogArticle
     */
    private function fillFromBlogArticle(BlogArticleData $blogArticleData, BlogArticle $blogArticle): void
    {
        $blogArticleData->names = $blogArticle->getNames();
        $blogArticleData->descriptions = $blogArticle->getDescriptions();
        $blogArticleData->perexes = $blogArticle->getPerexes();
        $blogArticleData->hidden = $blogArticle->isHidden();
        $blogArticleData->visibleOnHomepage = $blogArticle->isVisibleOnHomepage();
        $blogArticleData->publishDate = $blogArticle->getPublishDate();
        $blogArticleData->blogCategoriesByDomainId = $blogArticle->getBlogCategoriesIndexedByDomainId();

        foreach ($this->domain->getAllIds() as $domainId) {
            $blogArticleData->seoMetaDescriptions[$domainId] = $blogArticle->getSeoMetaDescription($domainId);
            $blogArticleData->seoTitles[$domainId] = $blogArticle->getSeoTitle($domainId);
            $blogArticleData->seoH1s[$domainId] = $blogArticle->getSeoH1($domainId);

            $mainFriendlyUrl = $this->friendlyUrlFacade->findMainFriendlyUrl($domainId, 'front_blogarticle_detail', $blogArticle->getId());
            $blogArticleData->urls->mainFriendlyUrlsByDomainId[$domainId] = $mainFriendlyUrl;
        }
    }
}
