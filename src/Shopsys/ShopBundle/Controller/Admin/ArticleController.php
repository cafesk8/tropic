<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Admin;

use Shopsys\FrameworkBundle\Controller\Admin\ArticleController as BaseArticleController;
use Shopsys\FrameworkBundle\Model\Article\Article as BaseArticle;
use Shopsys\ShopBundle\Model\Article\Article;

class ArticleController extends BaseArticleController
{
    public function listAction()
    {
        $gridTop = $this->getGrid(BaseArticle::PLACEMENT_TOP_MENU);
        $gridFooter = $this->getGrid(BaseArticle::PLACEMENT_FOOTER);
        $gridNone = $this->getGrid(BaseArticle::PLACEMENT_NONE);
        $gridShopping = $this->getGrid(Article::PLACEMENT_SHOPPING);
        $gridAbout = $this->getGrid(Article::PLACEMENT_ABOUT);
        $gridServices = $this->getGrid(Article::PLACEMENT_SERVICES);

        $articlesCountOnSelectedDomain = $this->articleFacade->getAllArticlesCountByDomainId($this->adminDomainTabsFacade->getSelectedDomainId());

        return $this->render('@ShopsysFramework/Admin/Content/Article/list.html.twig', [
            'gridViewTop' => $gridTop->createView(),
            'gridViewFooter' => $gridFooter->createView(),
            'gridViewNone' => $gridNone->createView(),
            'gridViewShopping' => $gridShopping->createView(),
            'gridViewAbout' => $gridAbout->createView(),
            'gridViewServices' => $gridServices->createView(),
            'articlesCountOnSelectedDomain' => $articlesCountOnSelectedDomain,
        ]);
    }
}
