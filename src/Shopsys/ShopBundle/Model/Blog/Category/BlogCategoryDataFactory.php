<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Blog\Category;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade;

class BlogCategoryDataFactory
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
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory $blogCategory
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryData
     */
    public function createFromBlogCategory(BlogCategory $blogCategory): BlogCategoryData
    {
        $blogCategoryData = new BlogCategoryData();
        $this->fillFromBlogCategory($blogCategoryData, $blogCategory);

        return $blogCategoryData;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryData
     */
    public function create(): BlogCategoryData
    {
        $blogCategoryData = new BlogCategoryData();
        $this->fillNew($blogCategoryData);

        return $blogCategoryData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryData $blogCategoryData
     */
    private function fillNew(BlogCategoryData $blogCategoryData): void
    {
        foreach ($this->domain->getAllIds() as $domainId) {
            $blogCategoryData->seoMetaDescriptions[$domainId] = null;
            $blogCategoryData->seoTitles[$domainId] = null;
            $blogCategoryData->seoH1s[$domainId] = null;
            $blogCategoryData->enabled[$domainId] = true;
        }

        foreach ($this->domain->getAllLocales() as $locale) {
            $blogCategoryData->names[$locale] = null;
        }
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryData $blogCategoryData
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory $blogCategory
     */
    private function fillFromBlogCategory(BlogCategoryData $blogCategoryData, BlogCategory $blogCategory): void
    {
        $blogCategoryData->names = $blogCategory->getNames();
        $blogCategoryData->parent = $blogCategory->getParent();

        foreach ($this->domain->getAllIds() as $domainId) {
            $blogCategoryData->seoMetaDescriptions[$domainId] = $blogCategory->getSeoMetaDescription($domainId);
            $blogCategoryData->seoTitles[$domainId] = $blogCategory->getSeoTitle($domainId);
            $blogCategoryData->seoH1s[$domainId] = $blogCategory->getSeoH1($domainId);
            $blogCategoryData->enabled[$domainId] = $blogCategory->isEnabled($domainId);

            $mainFriendlyUrl = $this->friendlyUrlFacade->findMainFriendlyUrl($domainId, 'front_blogcategory_detail', $blogCategory->getId());
            $blogCategoryData->urls->mainFriendlyUrlsByDomainId[$domainId] = $mainFriendlyUrl;
        }
    }
}
