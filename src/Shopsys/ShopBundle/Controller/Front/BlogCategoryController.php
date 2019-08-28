<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Front;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\ShopBundle\Model\Blog\Article\BlogArticleFacade;
use Shopsys\ShopBundle\Model\Blog\Category\BlogCategory;
use Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryFacade;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BlogCategoryController extends FrontBaseController
{
    private const BLOG_ARTICLES_PER_PAGE = 12;
    private const PAGE_QUERY_PARAMETER = 'page';

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryFacade
     */
    private $blogCategoryFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleFacade
     */
    private $blogArticleFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryFacade $blogCategoryFacade
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleFacade $blogArticleFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        BlogCategoryFacade $blogCategoryFacade,
        BlogArticleFacade $blogArticleFacade,
        Domain $domain
    ) {
        $this->blogCategoryFacade = $blogCategoryFacade;
        $this->domain = $domain;
        $this->blogArticleFacade = $blogArticleFacade;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function detailAction(Request $request, int $id): Response
    {
        $blogCategory = $this->blogCategoryFacade->getVisibleOnDomainById($this->domain->getId(), $id);

        $requestPage = $request->get(self::PAGE_QUERY_PARAMETER);

        if (!$this->isRequestPageValid($requestPage)) {
            return $this->redirectToRoute('front_blogcategory_detail', ['id' => BlogCategory::BLOG_MAIN_PAGE_CATEGORY_ID]);
        }

        $page = $requestPage === null ? 1 : (int)$requestPage;

        $blogArticlePaginationResult = $this->blogArticleFacade->getPaginationResultForListableInBlogCategory(
            $blogCategory,
            $this->domain->getId(),
            $this->domain->getLocale(),
            $page,
            self::BLOG_ARTICLES_PER_PAGE
        );

        $lastBlogCategoryForBlogArticlesByBlogArticleId = $this->blogCategoryFacade->getLastBlogCategoryForBlogArticlesByBlogArticleId(
            $blogArticlePaginationResult->getResults(),
            $this->domain->getId()
        );

        return $this->render('@ShopsysShop/Front/Content/Blog/Category/detail.html.twig', [
            'blogCategory' => $blogCategory,
            'isMainPage' => $blogCategory->isMainPage(),
            'blogArticlePaginationResult' => $blogArticlePaginationResult,
            'lastBlogCategoryForBlogArticlesByBlogArticleId' => $lastBlogCategoryForBlogArticlesByBlogArticleId,
        ]);
    }

    /**
     * @param string|null $page
     * @return bool
     */
    private function isRequestPageValid(?string $page): bool
    {
        return $page === null || (preg_match('@^([2-9]|[1-9][0-9]+)$@', $page));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(): Response
    {
        $childrenBlogCategories = $this->blogCategoryFacade->getAllVisibleChildrenByDomainId($this->domain->getId());

        return $this->render('@ShopsysShop/Front/Content/Blog/Category/list.html.twig', [
            'blogCategories' => $childrenBlogCategories,
        ]);
    }
}
