<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Model\Blog\Article\BlogArticle;
use App\Model\Blog\Article\BlogArticleFacade;
use App\Model\Blog\Category\BlogCategory;
use App\Model\Blog\Category\BlogCategoryFacade;
use App\Model\Heureka\HeurekaReviewFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BlogCategoryController extends FrontBaseController
{
    private const BLOG_ARTICLES_PER_PAGE = 12;
    private const PAGE_QUERY_PARAMETER = 'page';

    /**
     * @var \App\Model\Blog\Category\BlogCategoryFacade
     */
    private $blogCategoryFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \App\Model\Blog\Article\BlogArticleFacade
     */
    private $blogArticleFacade;

    /**
     * @var \App\Model\Heureka\HeurekaReviewFacade
     */
    private $heurekaReviewFacade;

    /**
     * @param \App\Model\Blog\Category\BlogCategoryFacade $blogCategoryFacade
     * @param \App\Model\Blog\Article\BlogArticleFacade $blogArticleFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Heureka\HeurekaReviewFacade $heurekaReviewFacade
     */
    public function __construct(
        BlogCategoryFacade $blogCategoryFacade,
        BlogArticleFacade $blogArticleFacade,
        Domain $domain,
        HeurekaReviewFacade $heurekaReviewFacade
    ) {
        $this->blogCategoryFacade = $blogCategoryFacade;
        $this->domain = $domain;
        $this->blogArticleFacade = $blogArticleFacade;
        $this->heurekaReviewFacade = $heurekaReviewFacade;
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

        return $this->render('Front/Content/Blog/Category/detail.html.twig', [
            'blogCategory' => $blogCategory,
            'isMainPage' => $blogCategory->isMainPage(),
            'blogArticlePaginationResult' => $blogArticlePaginationResult,
            'lastBlogCategoryForBlogArticlesByBlogArticleId' => $lastBlogCategoryForBlogArticlesByBlogArticleId,
            'heurekaReviews' => $this->heurekaReviewFacade->getLatestReviews($this->domain->getId()),
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
     * @param \App\Model\Blog\Article\BlogArticle $blogArticle
     * @param \App\Model\Blog\Category\BlogCategory|null $blogCategory
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(?BlogArticle $blogArticle, ?BlogCategory $blogCategory): Response
    {
        $childrenBlogCategories = $this->blogCategoryFacade->getAllVisibleChildrenByDomainId($this->domain->getId());
        $activeCategoryIds = [];

        if ($blogArticle) {
            $activeCategoryIds = $this->blogCategoryFacade->getBlogArticleBlogCategoryIdsWithDeepestLevel($blogArticle, $this->domain->getId());
        } elseif ($blogCategory) {
            $activeCategoryIds[] = $blogCategory->getId();
        }

        return $this->render('Front/Content/Blog/Category/list.html.twig', [
            'activeCategoryIds' => $activeCategoryIds,
            'blogCategories' => $childrenBlogCategories,
        ]);
    }
}
