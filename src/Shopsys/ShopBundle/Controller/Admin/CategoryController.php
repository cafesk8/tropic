<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Admin;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Router\Security\Annotation\CsrfProtection;
use Shopsys\FrameworkBundle\Controller\Admin\CategoryController as BaseCategoryController;
use Shopsys\FrameworkBundle\Form\Admin\Category\CategoryFormType;
use Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider;
use Shopsys\FrameworkBundle\Model\Category\CategoryDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Category\CategoryFacade;
use Shopsys\ShopBundle\Component\Redis\RedisFacade;
use Shopsys\ShopBundle\Model\Category\CategoryBlogArticle\CategoryBlogArticleFacade;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CategoryController extends BaseCategoryController
{
    /**
     * @var \Shopsys\ShopBundle\Model\Category\CategoryBlogArticle\CategoryBlogArticleFacade
     */
    private $categoryBlogArticleFacade;

    /**
     * @var \Shopsys\ShopBundle\Component\Redis\RedisFacade
     */
    private $redisFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Category\CategoryFacade $categoryFacade
     * @param \Shopsys\FrameworkBundle\Model\Category\CategoryDataFactoryInterface $categoryDataFactory
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider $breadcrumbOverrider
     * @param \Shopsys\ShopBundle\Model\Category\CategoryBlogArticle\CategoryBlogArticleFacade $categoryBlogArticleFacade
     * @param \Shopsys\ShopBundle\Component\Redis\RedisFacade $redisFacade
     */
    public function __construct(
        CategoryFacade $categoryFacade,
        CategoryDataFactoryInterface $categoryDataFactory,
        SessionInterface $session,
        Domain $domain,
        BreadcrumbOverrider $breadcrumbOverrider,
        CategoryBlogArticleFacade $categoryBlogArticleFacade,
        RedisFacade $redisFacade
    ) {
        parent::__construct($categoryFacade, $categoryDataFactory, $session, $domain, $breadcrumbOverrider);

        $this->categoryBlogArticleFacade = $categoryBlogArticleFacade;
        $this->redisFacade = $redisFacade;
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

        /** @var \Shopsys\ShopBundle\Model\Category\CategoryData $categoryData */
        $categoryData = $this->categoryDataFactory->createFromCategory($category);
        $categoryData->blogArticles = $this->categoryBlogArticleFacade->getAllBlogArticlesByCategory($category);

        $form = $this->createForm(CategoryFormType::class, $categoryData, [
            'category' => $category,
            'scenario' => CategoryFormType::SCENARIO_EDIT,
        ]);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->categoryFacade->edit($id, $categoryData);
            $this->categoryBlogArticleFacade->saveBlogArticlesToCategory($category, $categoryData->blogArticles);

            $this->getFlashMessageSender()->addSuccessFlashTwig(
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
            $this->getFlashMessageSender()->addErrorFlashTwig(t('Please check the correctness of all data filled.'));
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

        if ($form->isValid()) {
            $categoryData = $form->getData();

            $category = $this->categoryFacade->create($categoryData);
            $this->categoryBlogArticleFacade->saveBlogArticlesToCategory($category, $categoryData->blogArticles);

            $this->getFlashMessageSender()->addSuccessFlashTwig(
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
            $this->getFlashMessageSender()->addErrorFlashTwig(t('Please check the correctness of all data filled.'));
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

            $this->getFlashMessageSender()->addSuccessFlashTwig(
                t('Category <strong>{{ name }}</strong> deleted'),
                [
                    'name' => $fullName,
                ]
            );

            $this->redisFacade->clearCacheByPattern('twig:', 'categories');
        } catch (\Shopsys\FrameworkBundle\Model\Category\Exception\CategoryNotFoundException $ex) {
            $this->getFlashMessageSender()->addErrorFlash(t('Selected category doesn\'t exist.'));
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
}
