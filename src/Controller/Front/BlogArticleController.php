<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Model\Blog\Article\BlogArticle;
use App\Model\Blog\Article\BlogArticleFacade;
use App\Model\Blog\Category\BlogCategoryFacade;
use App\Model\Heureka\HeurekaReviewFacade;
use App\Model\Product\View\ListedProductViewElasticFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Symfony\Component\HttpFoundation\Response;

class BlogArticleController extends FrontBaseController
{
    /**
     * @var \App\Model\Blog\Article\BlogArticleFacade
     */
    private $blogArticleFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \App\Model\Blog\Category\BlogCategoryFacade
     */
    private $blogCategoryFacade;

    private ListedProductViewElasticFacade $listedProductViewElasticFacade;

    /**
     * @var \App\Model\Heureka\HeurekaReviewFacade
     */
    private $heurekaReviewFacade;

    /**
     * @param \App\Model\Blog\Article\BlogArticleFacade $blogArticleFacade
     * @param \App\Model\Blog\Category\BlogCategoryFacade $blogCategoryFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Product\View\ListedProductViewElasticFacade $listedProductViewElasticFacade
     * @param \App\Model\Heureka\HeurekaReviewFacade $heurekaReviewFacade
     */
    public function __construct(
        BlogArticleFacade $blogArticleFacade,
        BlogCategoryFacade $blogCategoryFacade,
        Domain $domain,
        ListedProductViewElasticFacade $listedProductViewElasticFacade,
        HeurekaReviewFacade $heurekaReviewFacade
    ) {
        $this->blogArticleFacade = $blogArticleFacade;
        $this->domain = $domain;
        $this->blogCategoryFacade = $blogCategoryFacade;
        $this->listedProductViewElasticFacade = $listedProductViewElasticFacade;
        $this->heurekaReviewFacade = $heurekaReviewFacade;
    }

    /**
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function detailAction(int $id): Response
    {
        $blogArticle = $this->blogArticleFacade->getVisibleOnDomainById(
            $this->domain->getCurrentDomainConfig(),
            $id
        );

        $blogCategoryIds = $this->blogCategoryFacade->getBlogArticleBlogCategoryIdsWithDeepestLevel($blogArticle, $this->domain->getId());

        return $this->render('Front/Content/Blog/Article/detail.html.twig', [
            'blogArticle' => $blogArticle,
            'activeCategories' => $blogCategoryIds,
            'domainId' => $this->domain->getId(),
            'heurekaReviews' => $this->heurekaReviewFacade->getLatestReviews(),
        ]);
    }

    /**
     * @param \App\Model\Blog\Article\BlogArticle $blogArticle
     * @param string $spanClass
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function mainBlogCategoryForBlogArticleAction(BlogArticle $blogArticle, ?string $spanClass): Response
    {
        return $this->render('Front/Content/Blog/Article/mainBlogCategoryForBlogArticle.html.twig', [
            'blogCategory' => $this->blogArticleFacade->findBlogArticleMainCategoryOnDomain($blogArticle, $this->domain->getId()),
            'spanClass' => $spanClass,
        ]);
    }

    /**
     * @param \App\Model\Blog\Article\BlogArticle $blogArticle
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function productsAction(BlogArticle $blogArticle): Response
    {
        return $this->render('Front/Content/Blog/Article/blogArticleProducts.html.twig', [
            'articleProducts' => $this->listedProductViewElasticFacade->getByArticle($blogArticle),
        ]);
    }
}
