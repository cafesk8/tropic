<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Front;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\ShopBundle\Component\Setting\Setting;
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
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \Shopsys\ShopBundle\Model\Article\ArticleFacade $articleFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(ArticleFacade $articleFacade, Domain $domain)
    {
        $this->articleFacade = $articleFacade;
        $this->domain = $domain;
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

    /**
     * @param string $placement
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function footerMenuAction(string $placement): Response
    {
        $articles = $this->articleFacade->getVisibleArticlesForPlacementOnCurrentDomain($placement);

        return $this->render('@ShopsysShop/Front/Content/Article/footerMenu.html.twig', [
            'articles' => $articles,
            'isPlacementAboutUs' => Article::PLACEMENT_ABOUT === $placement,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function headerListAction(): Response
    {
        $articles = $this->articleFacade->getVisibleArticlesOnCurrentDomainByPlacementAndLimit(Article::PLACEMENT_TOP_MENU, self::LIMIT_FOR_HEADER_ARTICLES);

        return $this->render('@ShopsysShop/Front/Content/Article/headerList.html.twig', [
            'articles' => $articles,
            'firstHeaderArticle' => $this->articleFacade->findArticleBySettingValueAndDomainId(Setting::FIRST_ARTICLE_ON_HEADER_MENU_ARTICLE_ID, $this->domain->getId()),
            'secondHeaderArticle' => $this->articleFacade->findArticleBySettingValueAndDomainId(Setting::SECOND_ARTICLE_ON_HEADER_MENU_ARTICLE_ID, $this->domain->getId()),
        ]);
    }
}
