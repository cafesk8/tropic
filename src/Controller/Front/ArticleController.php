<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Model\Article\Article;
use App\Model\Article\ArticleFacade;
use Symfony\Component\HttpFoundation\Response;

class ArticleController extends FrontBaseController
{
    private const LIMIT_FOR_HEADER_ARTICLES = 3;

    /**
     * @var \App\Model\Article\ArticleFacade
     */
    private $articleFacade;

    /**
     * @param \App\Model\Article\ArticleFacade $articleFacade
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

        return $this->render('Front/Content/Article/detail.html.twig', [
            'article' => $article,
        ]);
    }

    /**
     * @param int $id
     */
    public function detailForModalAction($id)
    {
        $article = $this->articleFacade->getVisibleById($id);

        return $this->render('Front/Content/Article/detailForModal.html.twig', [
            'article' => $article,
        ]);
    }

    /**
     * @param string $placement
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function footerMenuAction(string $placement): Response
    {
        $articles = $this->articleFacade->getVisibleArticlesForPlacementOnCurrentDomain($placement);

        return $this->render('Front/Content/Article/footerMenu.html.twig', [
            'articles' => $articles,
            'isPlacementAboutUs' => $placement === Article::PLACEMENT_ABOUT,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function headerListAction(): Response
    {
        $articles = $this->articleFacade->getVisibleArticlesOnCurrentDomainByPlacementAndLimit(Article::PLACEMENT_TOP_MENU, self::LIMIT_FOR_HEADER_ARTICLES);

        return $this->render('Front/Content/Article/headerList.html.twig', [
            'articles' => $articles,
        ]);
    }
}
