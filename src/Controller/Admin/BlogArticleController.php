<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Form\Admin\BlogArticleFormType;
use App\Model\Blog\Article\BlogArticleDataFactory;
use App\Model\Blog\Article\BlogArticleFacade;
use App\Model\Blog\Article\BlogArticleGridFactory;
use Shopsys\FrameworkBundle\Component\ConfirmDelete\ConfirmDeleteResponseFactory;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Router\Security\Annotation\CsrfProtection;
use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class BlogArticleController extends AdminBaseController
{
    private const ALL_DOMAINS = 0;
    private const SESSION_BLOG_ARTICLES_SELECTED_DOMAIN_ID = 'blog_articles_selected_domain_id';

    /**
     * @var \App\Model\Blog\Article\BlogArticleFacade
     */
    private $blogArticleFacade;

    /**
     * @var \App\Model\Blog\Article\BlogArticleDataFactory
     */
    private $blogArticleDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    private $adminDomainTabsFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider
     */
    private $breadcrumbOverrider;

    /**
     * @var \Shopsys\FrameworkBundle\Component\ConfirmDelete\ConfirmDeleteResponseFactory
     */
    private $confirmDeleteResponseFactory;

    /**
     * @var \App\Model\Blog\Article\BlogArticleGridFactory
     */
    private $blogArticleGridFactory;

    /**
     * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
     */
    private $session;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \App\Model\Blog\Article\BlogArticleFacade $blogArticleFacade
     * @param \App\Model\Blog\Article\BlogArticleDataFactory $blogArticleDataFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     * @param \Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider $breadcrumbOverrider
     * @param \Shopsys\FrameworkBundle\Component\ConfirmDelete\ConfirmDeleteResponseFactory $confirmDeleteResponseFactory
     * @param \App\Model\Blog\Article\BlogArticleGridFactory $blogArticleGridFactory
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        BlogArticleFacade $blogArticleFacade,
        BlogArticleDataFactory $blogArticleDataFactory,
        AdminDomainTabsFacade $adminDomainTabsFacade,
        BreadcrumbOverrider $breadcrumbOverrider,
        ConfirmDeleteResponseFactory $confirmDeleteResponseFactory,
        BlogArticleGridFactory $blogArticleGridFactory,
        SessionInterface $session,
        Domain $domain
    ) {
        $this->blogArticleFacade = $blogArticleFacade;
        $this->blogArticleDataFactory = $blogArticleDataFactory;
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
        $this->breadcrumbOverrider = $breadcrumbOverrider;
        $this->confirmDeleteResponseFactory = $confirmDeleteResponseFactory;
        $this->blogArticleGridFactory = $blogArticleGridFactory;
        $this->session = $session;
        $this->domain = $domain;
    }

    /**
     * @Route("/blog/article/list/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request): Response
    {
        if (count($this->domain->getAll()) > 1) {
            if ($request->query->has('domain')) {
                $domainId = (int)$request->query->get('domain');
            } else {
                $domainId = (int)$this->session->get(self::SESSION_BLOG_ARTICLES_SELECTED_DOMAIN_ID, self::ALL_DOMAINS);
            }
        } else {
            $domainId = self::ALL_DOMAINS;
        }

        if ($domainId !== self::ALL_DOMAINS) {
            try {
                $this->domain->getDomainConfigById($domainId);
            } catch (\Shopsys\FrameworkBundle\Component\Domain\Exception\InvalidDomainIdException $ex) {
                $domainId = self::ALL_DOMAINS;
            }
        }

        $this->session->set(self::SESSION_BLOG_ARTICLES_SELECTED_DOMAIN_ID, $domainId);

        $grid = $this->blogArticleGridFactory->create($domainId);
        $blogArticlesCountOnSelectedDomain = $this->blogArticleFacade->getAllArticlesCountByDomainId($this->adminDomainTabsFacade->getSelectedDomainId());

        return $this->render('Admin/Content/Blog/Article/list.html.twig', [
            'gridView' => $grid->createView(),
            'blogArticlesCountOnSelectedDomain' => $blogArticlesCountOnSelectedDomain,
        ]);
    }

    /**
     * @Route("/blog/article/edit/{id}", requirements={"id" = "\d+"})
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, int $id): Response
    {
        $blogArticle = $this->blogArticleFacade->getById($id);
        $blogArticleData = $this->blogArticleDataFactory->createFromBlogArticle($blogArticle);

        $form = $this->createForm(BlogArticleFormType::class, $blogArticleData, [
            'blogArticle' => $blogArticle,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->blogArticleFacade->edit($id, $blogArticleData);

            $this
                ->addSuccessFlashTwig(
                    t('Blog article <strong><a href="{{ url }}">{{ name }}</a></strong> was modified'),
                    [
                        'name' => $blogArticle->getName(),
                        'url' => $this->generateUrl('admin_blogarticle_edit', ['id' => $blogArticle->getId()]),
                    ]
                );
            return $this->redirectToRoute('admin_blogarticle_list');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addErrorFlashTwig(t('Please check the correctness of all data filled.'));
        }

        $this->breadcrumbOverrider->overrideLastItem(t('Editing blog article - %name%', ['%name%' => $blogArticle->getName()]));

        return $this->render('Admin/Content/Blog/Article/edit.html.twig', [
            'form' => $form->createView(),
            'blogArticle' => $blogArticle,
        ]);
    }

    /**
     * @Route("/blog/article/new/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request): Response
    {
        $blogArticleData = $this->blogArticleDataFactory->create();

        $form = $this->createForm(BlogArticleFormType::class, $blogArticleData, [
            'blogArticle' => null,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $blogArticle = $this->blogArticleFacade->create($blogArticleData);

            $this
                ->addSuccessFlashTwig(
                    t('Blog article <strong><a href="{{ url }}">{{ name }}</a></strong> created'),
                    [
                        'name' => $blogArticle->getName(),
                        'url' => $this->generateUrl('admin_blogarticle_edit', ['id' => $blogArticle->getId()]),
                    ]
                );
            return $this->redirectToRoute('admin_blogarticle_list');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addErrorFlashTwig(t('Please check the correctness of all data filled.'));
        }

        return $this->render('Admin/Content/Blog/Article/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/blog/article/delete/{id}", requirements={"id" = "\d+"})
     * @CsrfProtection
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(int $id): Response
    {
        try {
            $fullName = $this->blogArticleFacade->getById($id)->getName();

            $this->blogArticleFacade->delete($id);

            $this->addSuccessFlashTwig(
                t('Blog article <strong>{{ name }}</strong> deleted'),
                [
                    'name' => $fullName,
                ]
            );
        } catch (\Shopsys\FrameworkBundle\Model\Article\Exception\ArticleNotFoundException $ex) {
            $this->addErrorFlash(t('Selected blog article doesn\'t exist.'));
        }

        return $this->redirectToRoute('admin_blogarticle_list');
    }

    /**
     * @Route("/blog/article/delete-confirm/{id}", requirements={"id" = "\d+"})
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteConfirmAction(int $id): Response
    {
        $message = t('Do you really want to remove this blog article?');

        return $this->confirmDeleteResponseFactory->createDeleteResponse($message, 'admin_blogarticle_delete', $id);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listDomainTabsAction(): Response
    {
        $domainId = $this->session->get(self::SESSION_BLOG_ARTICLES_SELECTED_DOMAIN_ID, self::ALL_DOMAINS);

        return $this->render('Admin/Content/Blog/Article/domainTabs.html.twig', [
            'domainConfigs' => $this->domain->getAll(),
            'selectedDomainId' => $domainId,
        ]);
    }
}
