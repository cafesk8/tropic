<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Admin;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Router\Security\Annotation\CsrfProtection;
use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider;
use Shopsys\ShopBundle\Form\Admin\BlogCategoryFormType;
use Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryDataFactory;
use Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryFacade;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class BlogCategoryController extends AdminBaseController
{
    private const ALL_DOMAINS = 0;
    private const SESSION_BLOG_CATEGORIES_SELECTED_DOMAIN_ID = 'blog_categories_selected_domain_id';

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryFacade
     */
    private $blogCategoryFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryDataFactory
     */
    private $blogCategoryDataFactory;

    /**
     * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
     */
    private $session;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider
     */
    private $breadcrumbOverrider;

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryFacade $blogCategoryFacade
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryDataFactory $blogCategoryDataFactory
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider $breadcrumbOverrider
     */
    public function __construct(
        BlogCategoryFacade $blogCategoryFacade,
        BlogCategoryDataFactory $blogCategoryDataFactory,
        SessionInterface $session,
        Domain $domain,
        BreadcrumbOverrider $breadcrumbOverrider
    ) {
        $this->blogCategoryFacade = $blogCategoryFacade;
        $this->blogCategoryDataFactory = $blogCategoryDataFactory;
        $this->session = $session;
        $this->domain = $domain;
        $this->breadcrumbOverrider = $breadcrumbOverrider;
    }

    /**
     * @Route("/blog/category/edit/{id}", requirements={"id" = "\d+"})
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, int $id): Response
    {
        $blogCategory = $this->blogCategoryFacade->getById($id);
        $blogCategoryData = $this->blogCategoryDataFactory->createFromBlogCategory($blogCategory);

        $form = $this->createForm(BlogCategoryFormType::class, $blogCategoryData, [
            'blogCategory' => $blogCategory,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->blogCategoryFacade->edit($id, $blogCategoryData);

            $this->getFlashMessageSender()->addSuccessFlashTwig(
                t('Blog category <strong><a href="{{ url }}">{{ name }}</a></strong> was modified'),
                [
                    'name' => $blogCategory->getName(),
                    'url' => $this->generateUrl('admin_blogcategory_edit', ['id' => $blogCategory->getId()]),
                ]
            );

            return $this->redirectToRoute('admin_blogcategory_list');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->getFlashMessageSender()->addErrorFlashTwig(t('Please check the correctness of all data filled.'));
        }

        $this->breadcrumbOverrider->overrideLastItem(t('Editing blog category - %name%', ['%name%' => $blogCategory->getName()]));

        return $this->render('@ShopsysShop/Admin/Content/Blog/Category/edit.html.twig', [
            'form' => $form->createView(),
            'blogCategory' => $blogCategory,
        ]);
    }

    /**
     * @Route("/blog/category/new/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request): Response
    {
        $blogCategoryData = $this->blogCategoryDataFactory->create();

        $form = $this->createForm(BlogCategoryFormType::class, $blogCategoryData, [
            'blogCategory' => null,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $blogCategory = $this->blogCategoryFacade->create($blogCategoryData);

            $this->getFlashMessageSender()->addSuccessFlashTwig(
                t('Blog category <strong><a href="{{ url }}">{{ name }}</a></strong> created'),
                [
                    'name' => $blogCategory->getName(),
                    'url' => $this->generateUrl('admin_blogcategory_edit', ['id' => $blogCategory->getId()]),
                ]
            );

            return $this->redirectToRoute('admin_blogcategory_list');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->getFlashMessageSender()->addErrorFlashTwig(t('Please check the correctness of all data filled.'));
        }

        return $this->render('@ShopsysShop/Admin/Content/Blog/Category/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/blog/category/list/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request): Response
    {
        if (count($this->domain->getAll()) > 1) {
            if ($request->query->has('domain')) {
                $domainId = (int)$request->query->get('domain');
            } else {
                $domainId = (int)$this->session->get(self::SESSION_BLOG_CATEGORIES_SELECTED_DOMAIN_ID, self::ALL_DOMAINS);
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

        $this->session->set(self::SESSION_BLOG_CATEGORIES_SELECTED_DOMAIN_ID, $domainId);

        if ($domainId === self::ALL_DOMAINS) {
            $blogCategoriesWithPreloadedChildren = $this->blogCategoryFacade->getAllBlogCategoriesWithPreloadedChildren($request->getLocale());
        } else {
            $blogCategoriesWithPreloadedChildren = $this->blogCategoryFacade->getVisibleBlogCategoriesWithPreloadedChildrenOnDomain($domainId, $request->getLocale());
        }

        return $this->render('@ShopsysShop/Admin/Content/Blog/Category/list.html.twig', [
            'blogCategoriesWithPreloadedChildren' => $blogCategoriesWithPreloadedChildren,
            'isForAllDomains' => ($domainId === self::ALL_DOMAINS),
        ]);
    }

    /**
     * @Route("/blog/category/save-order/", methods={"post"}, condition="request.isXmlHttpRequest()")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveOrderAction(Request $request): Response
    {
        $blogCategoriesOrderingData = $request->get('categoriesOrderingData');

        $parentIdByBlogCategoryId = [];
        foreach ($blogCategoriesOrderingData as $blogCategoryOrderingData) {
            $blogCategoryId = (int)$blogCategoryOrderingData['categoryId'];
            $parentId = $blogCategoryOrderingData['parentId'] === '' ? null : (int)$blogCategoryOrderingData['parentId'];
            $parentIdByBlogCategoryId[$blogCategoryId] = $parentId;
        }

        $this->blogCategoryFacade->editOrdering($parentIdByBlogCategoryId);

        //every controller action has to return some Response, even if a response is empty. Result of this action is
        //processed by JS
        return new Response();
    }

    /**
     * @Route("/blog/category/delete/{id}", requirements={"id" = "\d+"})
     * @CsrfProtection
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(int $id): Response
    {
        try {
            $fullName = $this->blogCategoryFacade->getById($id)->getName();

            $this->blogCategoryFacade->deleteById($id);

            $this->getFlashMessageSender()->addSuccessFlashTwig(
                t('Blog category <strong>{{ name }}</strong> deleted'),
                [
                    'name' => $fullName,
                ]
            );
        } catch (\Shopsys\ShopBundle\Model\Blog\Category\Exception\BlogCategoryNotFoundException $ex) {
            $this->getFlashMessageSender()->addErrorFlash(t('Selected blog category doesn\'t exist.'));
        }

        return $this->redirectToRoute('admin_blogcategory_list');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listDomainTabsAction(): Response
    {
        $domainId = $this->session->get(self::SESSION_BLOG_CATEGORIES_SELECTED_DOMAIN_ID, self::ALL_DOMAINS);

        return $this->render('@ShopsysShop/Admin/Content/Blog/Category/domainTabs.html.twig', [
            'domainConfigs' => $this->domain->getAll(),
            'selectedDomainId' => $domainId,
        ]);
    }

    /**
     * @Route("/blog/category/branch/{domainId}/{id}", requirements={"domainId" = "\d+", "id" = "\d+"}, condition="request.isXmlHttpRequest()")
     * @param int $domainId
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function loadBranchJsonAction(int $domainId, int $id): JsonResponse
    {
        $blogParentCategory = $this->blogCategoryFacade->getById($id);
        $blogCategories = $blogParentCategory->getChildren();

        $blogCategoriesData = [];
        foreach ($blogCategories as $blogCategory) {
            $blogCategoriesData[] = [
                'id' => $blogCategory->getId(),
                'categoryName' => $blogCategory->getName(),
                'isVisible' => $blogCategory->isVisible($domainId),
                'hasChildren' => $blogCategory->hasChildren(),
                'loadUrl' => $this->generateUrl('admin_blogcategory_loadbranchjson', [
                    'domainId' => $domainId,
                    'id' => $blogCategory->getId(),
                ]),
            ];
        }

        return $this->json($blogCategoriesData);
    }
}
