<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Front;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\ShopBundle\Model\Blog\Article\BlogArticle;
use Shopsys\ShopBundle\Model\Blog\Article\BlogArticleFacade;
use Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryFacade;
use Symfony\Component\HttpFoundation\Response;

class BlogArticleController extends FrontBaseController
{
    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleFacade
     */
    private $blogArticleFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryFacade
     */
    private $blogCategoryFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleFacade $blogArticleFacade
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryFacade $blogCategoryFacade
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

        return $this->render('@ShopsysShop/Front/Content/Blog/Article/detail.html.twig', [
            'blogArticle' => $blogArticle,
            'activeCategories' => $blogCategoryIds,
            'domainId' => $this->domain->getId(),
        ]);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle $blogArticle
     * @param string $spanClass
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function mainBlogCategoryForBlogArticleAction(BlogArticle $blogArticle, ?string $spanClass): Response
    {
        return $this->render('@ShopsysShop/Front/Content/Blog/Article/mainBlogCategoryForBlogArticle.html.twig', [
            'blogCategory' => $this->blogArticleFacade->findBlogArticleMainCategoryOnDomain($blogArticle, $this->domain->getId()),
            'spanClass' => $spanClass,
        ]);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle $blogArticle
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function productsAction(?BlogArticle $blogArticle): Response
    {
        return $this->render('@ShopsysShop/Front/Content/Blog/Article/blogArticleProducts.html.twig', [
            'articleProducts' => $blogArticle->getProducts(),
        ]);
    }
}
