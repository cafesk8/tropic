<?php

declare(strict_types=1);

namespace App\Controller\Front;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Category\TopCategory\TopCategoryFacade;
use App\Model\Category\Category;
use App\Model\Category\CategoryFacade;
use App\Model\Category\HorizontalCategoryFacade;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends FrontBaseController
{
    /**
     * @var \App\Model\Category\CategoryFacade
     */
    private $categoryFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Category\TopCategory\TopCategoryFacade
     */
    private $topCategoryFacade;

    /**
     * @var \App\Model\Category\HorizontalCategoryFacade
     */
    private $horizontalCategoryFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     * @param \Shopsys\FrameworkBundle\Model\Category\TopCategory\TopCategoryFacade $topCategoryFacade
     * @param \App\Model\Category\HorizontalCategoryFacade $horizontalCategoryFacade
     */
    public function __construct(
        Domain $domain,
        CategoryFacade $categoryFacade,
        TopCategoryFacade $topCategoryFacade,
        HorizontalCategoryFacade $horizontalCategoryFacade
    ) {
        $this->domain = $domain;
        $this->categoryFacade = $categoryFacade;
        $this->topCategoryFacade = $topCategoryFacade;
        $this->horizontalCategoryFacade = $horizontalCategoryFacade;
    }

    /**
     * @param bool $dropdownMenu
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function hoverMenuAction(bool $dropdownMenu = true): Response
    {
        $categoriesWithLazyLoadedVisibleChildren = $this->categoryFacade->getCategoriesWithLazyLoadedVisibleAndListableChildrenForParent(
            $this->categoryFacade->getRootCategory(),
            $this->domain->getCurrentDomainConfig()
        );

        $categoriesForFirstColumn = $this->categoryFacade->getAllVisibleAndListableCategoriesForFirstColumnByDomainId($this->domain->getId());

        return $this->render('Front/Content/Category/hoverMenu.html.twig', [
            'categoriesWithLazyLoadedVisibleChildren' => $categoriesWithLazyLoadedVisibleChildren,
            'categoriesForFirstColumn' => $categoriesForFirstColumn,
            'categoriesIdsForFirstColumn' => array_map(function (Category $category) {
                return $category->getId();
            }, $categoriesForFirstColumn),
            'dropdownMenu' => $dropdownMenu,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function topAction(): Response
    {
        return $this->render('Front/Content/Category/top.html.twig', [
            'categories' => $this->topCategoryFacade->getVisibleCategoriesByDomainId($this->domain->getId()),
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function horizontalMenuAction(): Response
    {
        $categories = $this->horizontalCategoryFacade->getCategoriesForHorizontalMenuOnCurrentDomain();

        return $this->render('Front/Inline/Category/horizontalMenu.html.twig', [
            'categories' => $categories,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function horizontalMenuMobileAction(): Response
    {
        $categories = $this->horizontalCategoryFacade->getCategoriesForHorizontalMenuOnCurrentDomain();

        return $this->render('Front/Inline/Category/horizontalMenuMobile.html.twig', [
            'categories' => $categories,
        ]);
    }
}
