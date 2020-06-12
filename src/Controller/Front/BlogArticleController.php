<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Model\Blog\Article\BlogArticle;
use App\Model\Blog\Article\BlogArticleFacade;
use App\Model\Blog\Category\BlogCategoryFacade;
use App\Model\Product\Product;
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

    /**
     * @param \App\Model\Blog\Article\BlogArticleFacade $blogArticleFacade
     * @param \App\Model\Blog\Category\BlogCategoryFacade $blogCategoryFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(BlogArticleFacade $blogArticleFacade, BlogCategoryFacade $blogCategoryFacade, Domain $domain)
    {
        $this->blogArticleFacade = $blogArticleFacade;
        $this->domain = $domain;
        $this->blogCategoryFacade = $blogCategoryFacade;
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
        $offeredArticleProducts = $this->filterOnlyOfferedProducts($blogArticle->getProducts());

        return $this->render('Front/Content/Blog/Article/detail.html.twig', [
            'blogArticle' => $blogArticle,
            'activeCategories' => $blogCategoryIds,
            'domainId' => $this->domain->getId(),
            'articleProducts' => $offeredArticleProducts,
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
     * @param \App\Model\Product\Product[] $articleProducts
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function productsAction(?BlogArticle $blogArticle, array $articleProducts): Response
    {
        return $this->render('Front/Content/Blog/Article/blogArticleProducts.html.twig', [
            'articleProducts' => $articleProducts,
        ]);
    }

    /**
     * @param \App\Model\Product\Product[] $products
     * @return \App\Model\Product\Product[]
     */
    private function filterOnlyOfferedProducts(array $products): array
    {
        return array_filter($products, function (Product $product) {
            if ($product->getCalculatedSellingDenied() || !$product->isShownOnDomain($this->domain->getId())) {
                return false;
            }

            return true;
        });
    }
}
