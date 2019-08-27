<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Front;

use Shopsys\ShopBundle\Model\Article\Article;
use Shopsys\ShopBundle\Model\Article\ArticleFacade;
use Symfony\Component\HttpFoundation\Response;

class ArticleController extends FrontBaseController
{
    private const LIMIT_FOR_HEADER_ARTICLES = 3;

    /**
     * @var \Shopsys\ShopBundle\Model\Article\ArticleFacade
     */
    private $articleFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Article\ArticleFacade $articleFacade
     */
    public function __construct(ArticleFacade $articleFacade)
    {
        $this->articleFacade = $articleFacade;
    }

    /**
     * @param int $id
     */
    public function detailAction($id)
    {
        $article = $this->articleFacade->getVisibleById($id);

        return $this->render('@ShopsysShop/Front/Content/Article/detail.html.twig', [
            'article' => $article,
        ]);
    }

    /**
     * @param int $id
     */
    public function detailForModalAction($id)
    {
        $article = $this->articleFacade->getVisibleById($id);

        return $this->render('@ShopsysShop/Front/Content/Article/detailForModal.html.twig', [
            'article' => $article,
        ]);
    }

    public function menuAction()
    {
        $articles = $this->articleFacade->getVisibleArticlesForPlacementOnCurrentDomain(Article::PLACEMENT_TOP_MENU);

        return $this->render('@ShopsysShop/Front/Content/Article/menu.html.twig', [
            'articles' => $articles,
        ]);
    }

    public function footerShoppingAction()
    {
        $articles = $this->articleFacade->getVisibleArticlesForPlacementOnCurrentDomain(Article::PLACEMENT_SHOPPING);

        return $this->render('@ShopsysShop/Front/Content/Article/footerMenu.html.twig', [
            'articles' => $articles,
        ]);
    }

    public function footerAboutAction()
    {
        $articles = $this->articleFacade->getVisibleArticlesForPlacementOnCurrentDomain(Article::PLACEMENT_ABOUT);

        return $this->render('@ShopsysShop/Front/Content/Article/footerMenu.html.twig', [
            'articles' => $articles,
        ]);
    }

    public function footerServicesAction()
    {
        $articles = $this->articleFacade->getVisibleArticlesForPlacementOnCurrentDomain(Article::PLACEMENT_SERVICES);

        return $this->render('@ShopsysShop/Front/Content/Article/footerMenu.html.twig', [
            'articles' => $articles,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function headerListAction(): Response
    {
        $articles = $this->articleFacade->getVisibleArticlesOnCurrentDomainByPlacementAndLimit(Article::PLACEMENT_TOP_MENU, self::LIMIT_FOR_HEADER_ARTICLES);

        return $this->render('@ShopsysShop/Front/Content/Article/headerList.twig', [
            'articles' => $articles,
        ]);
    }
}
