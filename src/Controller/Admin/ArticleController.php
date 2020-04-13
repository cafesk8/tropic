<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Article\Article;
use Shopsys\FrameworkBundle\Controller\Admin\ArticleController as BaseArticleController;
use Shopsys\FrameworkBundle\Model\Article\Article as BaseArticle;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @property \App\Model\Article\ArticleDataFactory $articleDataFactory
 * @method __construct(\App\Model\Article\ArticleFacade $articleFacade, \App\Model\Article\ArticleDataFactory $articleDataFactory, \Shopsys\FrameworkBundle\Component\Grid\GridFactory $gridFactory, \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade, \Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider $breadcrumbOverrider, \Shopsys\FrameworkBundle\Component\ConfirmDelete\ConfirmDeleteResponseFactory $confirmDeleteResponseFactory, \Shopsys\FrameworkBundle\Model\LegalConditions\LegalConditionsFacade $legalConditionsFacade, \Shopsys\FrameworkBundle\Model\Cookies\CookiesFacade $cookiesFacade)
 */
class ArticleController extends BaseArticleController
{
    /**
     * @var \App\Model\Article\ArticleFacade
     */
    protected $articleFacade;

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(): Response
    {
        $gridTop = $this->getGrid(BaseArticle::PLACEMENT_TOP_MENU);
        $gridFooter = $this->getGrid(BaseArticle::PLACEMENT_FOOTER);
        $gridNone = $this->getGrid(BaseArticle::PLACEMENT_NONE);
        $gridShopping = $this->getGrid(Article::PLACEMENT_SHOPPING);
        $gridServices = $this->getGrid(Article::PLACEMENT_SERVICES);

        $articlesCountOnSelectedDomain = $this->articleFacade->getAllArticlesCountByDomainId($this->adminDomainTabsFacade->getSelectedDomainId());

        return $this->render('@ShopsysFramework/Admin/Content/Article/list.html.twig', [
            'gridViewTop' => $gridTop->createView(),
            'gridViewFooter' => $gridFooter->createView(),
            'gridViewNone' => $gridNone->createView(),
            'gridViewShopping' => $gridShopping->createView(),
            'gridViewServices' => $gridServices->createView(),
            'articlesCountOnSelectedDomain' => $articlesCountOnSelectedDomain,
        ]);
    }

    /**
     * @Route("/article/delete-confirm/{id}", requirements={"id" = "\d+"})
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteConfirmAction($id): Response
    {
        $article = $this->articleFacade->getById($id);
        if ($this->legalConditionsFacade->isArticleUsedAsLegalConditions($article)) {
            $message = t(
                'Article "%name%" set for displaying legal conditions. This setting will be lost. Do you really want to delete it?',
                ['%name%' => $article->getName()]
            );
        } elseif ($this->cookiesFacade->isArticleUsedAsCookiesInfo($article)) {
            $message = t(
                'Article "%name%" set for displaying cookies information. This setting will be lost. Do you really want to delete it?',
                ['%name%' => $article->getName()]
            );
        } elseif ($this->articleFacade->isArticleUsedForLoyaltyProgram($article)) {
            $message = t(
                'Článek "%name%" je nastaven pro zobrazení informací o Věrnostním programu. Toto nastavení bude ztraceno. Opravdu si jej přejete smazat?',
                ['%name%' => $article->getName()]
            );
        } else {
            $message = t('Do you really want to remove this article?');
        }

        return $this->confirmDeleteResponseFactory->createDeleteResponse($message, 'admin_article_delete', $id);
    }
}
