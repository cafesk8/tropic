<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Component\Form\FormBuilderHelper;
use App\Component\Redis\RedisFacade;
use App\Model\Category\CategoryBlogArticle\CategoryBlogArticleFacade;
use App\Model\Category\CategoryBrand\CategoryBrandFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Router\Security\Annotation\CsrfProtection;
use Shopsys\FrameworkBundle\Controller\Admin\CategoryController as BaseCategoryController;
use Shopsys\FrameworkBundle\Form\Admin\Category\CategoryFormType;
use Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider;
use Shopsys\FrameworkBundle\Model\Category\CategoryDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Category\CategoryFacade;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @property \App\Model\Category\CategoryDataFactory $categoryDataFactory
 * @property \App\Model\Category\CategoryFacade $categoryFacade
 */
class CategoryController extends BaseCategoryController
{
    /**
     * @var \App\Component\Form\FormBuilderHelper
     */
    protected $formBuilderHelper;

    /**
     * @var \App\Model\Category\CategoryBlogArticle\CategoryBlogArticleFacade
     */
    private $categoryBlogArticleFacade;

    /**
     * @var \App\Component\Redis\RedisFacade
     */
    private $redisFacade;

    /**
     * @var \App\Model\Category\CategoryBrand\CategoryBrandFacade
     */
    private $categoryBrandFacade;

    /**
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     * @param \App\Model\Category\CategoryDataFactory $categoryDataFactory
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider $breadcrumbOverrider
     * @param \App\Model\Category\CategoryBlogArticle\CategoryBlogArticleFacade $categoryBlogArticleFacade
     * @param \App\Component\Redis\RedisFacade $redisFacade
     * @param \App\Component\Form\FormBuilderHelper $formBuilderHelper
     * @param \App\Model\Category\CategoryBrand\CategoryBrandFacade $categoryBrandFacade
     */
    public function __construct(
        CategoryFacade $categoryFacade,
        CategoryDataFactoryInterface $categoryDataFactory,
        SessionInterface $session,
        Domain $domain,
        BreadcrumbOverrider $breadcrumbOverrider,
        CategoryBlogArticleFacade $categoryBlogArticleFacade,
        RedisFacade $redisFacade,
        FormBuilderHelper $formBuilderHelper,
        CategoryBrandFacade $categoryBrandFacade
    ) {
        parent::__construct($categoryFacade, $categoryDataFactory, $session, $domain, $breadcrumbOverrider);

        $this->categoryBlogArticleFacade = $categoryBlogArticleFacade;
        $this->redisFacade = $redisFacade;
        $this->formBuilderHelper = $formBuilderHelper;
        $this->categoryBrandFacade = $categoryBrandFacade;
    }

    /**
     * @Route("/category/edit/{id}", requirements={"id" = "\d+"})
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $id): Response
    {
        $category = $this->categoryFacade->getById($id);

        /** @var \App\Model\Category\CategoryData $categoryData */
        $categoryData = $this->categoryDataFactory->createFromCategory($category);
        $categoryData->blogArticles = $this->categoryBlogArticleFacade->getAllBlogArticlesByCategory($category);

        $form = $this->createForm(CategoryFormType::class, $categoryData, [
            'category' => $category,
            'scenario' => CategoryFormType::SCENARIO_EDIT,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->categoryFacade->edit($id, $categoryData);
            $this->categoryBlogArticleFacade->saveBlogArticlesToCategory($category, $categoryData->blogArticles);
            $this->categoryBrandFacade->saveCategoryBrandsToCategory($category, $categoryData->categoryBrands);

            $this->addSuccessFlashTwig(
                t('Category<strong><a href="{{ url }}">{{ name }}</a></strong> was modified'),
                [
                    'name' => $category->getName(),
                    'url' => $this->generateUrl('admin_category_edit', ['id' => $category->getId()]),
                ]
            );
            $this->redisFacade->clearCacheByPattern('twig:', 'categories');

            return $this->redirectToRoute('admin_category_list');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addErrorFlashTwig(t('Please check the correctness of all data filled.'));
        }

        $this->breadcrumbOverrider->overrideLastItem(t('Editing category - %name%', ['%name%' => $category->getName()]));

        return $this->render('@ShopsysFramework/Admin/Content/Category/edit.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
        ]);
    }

    /**
     * @Route("/category/new/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request): Response
    {
        $categoryData = $this->categoryDataFactory->create();

        $form = $this->createForm(CategoryFormType::class, $categoryData, [
            'category' => null,
            'scenario' => CategoryFormType::SCENARIO_CREATE,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $categoryData = $form->getData();

            $category = $this->categoryFacade->create($categoryData);
            $this->categoryBlogArticleFacade->saveBlogArticlesToCategory($category, $categoryData->blogArticles);

            $this->addSuccessFlashTwig(
                t('Category <strong><a href="{{ url }}">{{ name }}</a></strong> created'),
                [
                    'name' => $category->getName(),
                    'url' => $this->generateUrl('admin_category_edit', ['id' => $category->getId()]),
                ]
            );
            $this->redisFacade->clearCacheByPattern('twig:', 'categories');

            return $this->redirectToRoute('admin_category_list');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addErrorFlashTwig(t('Please check the correctness of all data filled.'));
        }

        return $this->render('@ShopsysFramework/Admin/Content/Category/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/category/delete/{id}", requirements={"id" = "\d+"})
     * @CsrfProtection
     * @param int $id
     */
    public function deleteAction($id)
    {
        try {
            $fullName = $this->categoryFacade->getById($id)->getName();

            $this->categoryFacade->deleteById($id);

            $this->addSuccessFlashTwig(
                t('Category <strong>{{ name }}</strong> deleted'),
                [
                    'name' => $fullName,
                ]
            );

            $this->redisFacade->clearCacheByPattern('twig:', 'categories');
        } catch (\Shopsys\FrameworkBundle\Model\Category\Exception\CategoryNotFoundException $ex) {
            $this->addErrorFlash(t('Selected category doesn\'t exist.'));
        }

        return $this->redirectToRoute('admin_category_list');
    }

    /**
     * @Route("/category/save-order/", methods={"post"})
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function saveOrderAction(Request $request)
    {
        $categoriesOrderingData = $request->get('categoriesOrderingData');

        $parentIdByCategoryId = [];
        foreach ($categoriesOrderingData as $categoryOrderingData) {
            $categoryId = (int)$categoryOrderingData['categoryId'];
            $parentId = $categoryOrderingData['parentId'] === '' ? null : (int)$categoryOrderingData['parentId'];
            $parentIdByCategoryId[$categoryId] = $parentId;
        }

        $this->categoryFacade->editOrdering($parentIdByCategoryId);
        $this->redisFacade->clearCacheByPattern('twig:', 'categories');

        return new Response('OK - dummy');
    }

    /**
     * @Route("/category/list/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function listAction(Request $request)
    {
        if (count($this->domain->getAll()) > 1) {
            if ($request->query->has('domain')) {
                $domainId = (int)$request->query->get('domain');
            } else {
                $domainId = (int)$this->session->get('categories_selected_domain_id', static::ALL_DOMAINS);
            }
        } else {
            $domainId = static::ALL_DOMAINS;
        }

        if ($domainId !== static::ALL_DOMAINS) {
            try {
                $this->domain->getDomainConfigById($domainId);
            } catch (\Shopsys\FrameworkBundle\Component\Domain\Exception\InvalidDomainIdException $ex) {
                $domainId = static::ALL_DOMAINS;
            }
        }

        $this->session->set('categories_selected_domain_id', $domainId);

        if ($domainId === static::ALL_DOMAINS) {
            $categoriesWithPreloadedChildren = $this->categoryFacade->getAllCategoriesWithPreloadedChildren($request->getLocale());
        } else {
            $categoriesWithPreloadedChildren = $this->categoryFacade->getVisibleCategoriesWithPreloadedChildrenForDomain($domainId, $request->getLocale());
        }

        return $this->render('Admin/Content/Category/list.html.twig', [
            'categoriesWithPreloadedChildren' => $categoriesWithPreloadedChildren,
            'isForAllDomains' => ($domainId === static::ALL_DOMAINS),
            'disableTreeEditing' => $this->formBuilderHelper->getDisableFields(),
        ]);
    }
}
