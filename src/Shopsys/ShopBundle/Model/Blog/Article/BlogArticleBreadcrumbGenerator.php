<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Blog\Article;

use Shopsys\FrameworkBundle\Component\Breadcrumb\BreadcrumbGeneratorInterface;
use Shopsys\FrameworkBundle\Component\Breadcrumb\BreadcrumbItem;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\ShopBundle\Model\Blog\Category\BlogCategory;
use Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryFacade;

class BlogArticleBreadcrumbGenerator implements BreadcrumbGeneratorInterface
{
    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleRepository
     */
    private $blogArticleRepository;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryFacade
     */
    private $blogCategoryFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleRepository $blogArticleRepository
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryFacade $blogCategoryFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        BlogArticleRepository $blogArticleRepository,
        BlogCategoryFacade $blogCategoryFacade,
        Domain $domain
    ) {
        $this->blogArticleRepository = $blogArticleRepository;
        $this->blogCategoryFacade = $blogCategoryFacade;
        $this->domain = $domain;
    }

    /**
     * {@inheritDoc}
     */
    public function getBreadcrumbItems($routeName, array $routeParameters = []): array
    {
        $blogArticle = $this->blogArticleRepository->getById($routeParameters['id']);

        $blogArticleMainCategoryOnDomain = $this->blogCategoryFacade->getBlogArticleMainBlogCategoryOnDomain(
            $blogArticle,
            $this->domain->getId()
        );

        $breadcrumbItems = $this->getBlogCategoryBreadcrumbItems($blogArticleMainCategoryOnDomain);

        $breadcrumbItems[] = new BreadcrumbItem(
            $blogArticle->getName()
        );

        return $breadcrumbItems;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory $blogCategory
     * @return \Shopsys\FrameworkBundle\Component\Breadcrumb\BreadcrumbItem[]
     */
    private function getBlogCategoryBreadcrumbItems(BlogCategory $blogCategory): array
    {
        $blogCategoriesInPath = $this->blogCategoryFacade->getVisibleBlogCategoriesInPathFromRootOnDomain(
            $blogCategory,
            $this->domain->getId()
        );

        $breadcrumbItems = [];
        foreach ($blogCategoriesInPath as $blogCategoryInPath) {
            $breadcrumbItems[] = new BreadcrumbItem(
                $blogCategoryInPath->getName(),
                'front_blogcategory_detail',
                ['id' => $blogCategoryInPath->getId()]
            );
        }

        return $breadcrumbItems;
    }

    /**
     * {@inheritDoc}
     */
    public function getRouteNames(): array
    {
        return ['front_blogarticle_detail'];
    }
}
