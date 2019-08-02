<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Front;

use Shopsys\FrameworkBundle\Model\Article\ArticleFacade;
use Shopsys\ShopBundle\Model\Article\Article;

class ArticleController extends FrontBaseController
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Article\ArticleFacade
     */
    private $articleFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Article\ArticleFacade $articleFacade
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
}
