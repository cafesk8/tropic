<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Model\Category\CategoryFacade;
use App\Model\Category\HorizontalCategoryFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
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
     * @var \App\Model\Category\HorizontalCategoryFacade
     */
    private $horizontalCategoryFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Category\CategoryWithLazyLoadedVisibleChildren[]|null
     */
    private $categoriesWithLazyLoadedVisibleChildren;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     * @param \App\Model\Category\HorizontalCategoryFacade $horizontalCategoryFacade
     */
    public function __construct(
        Domain $domain,
        CategoryFacade $categoryFacade,
        HorizontalCategoryFacade $horizontalCategoryFacade
    ) {
        $this->domain = $domain;
        $this->categoryFacade = $categoryFacade;
        $this->horizontalCategoryFacade = $horizontalCategoryFacade;
    }

    /**
     * @param bool $dropdownMenu
     * @param bool $showImage
     * @param bool $children
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function hoverMenuAction(bool $dropdownMenu = true, bool $showImage = true, bool $children = false): Response
    {
        return $this->render('Front/Content/Category/hoverMenu.html.twig', [
            'categoriesWithLazyLoadedVisibleChildren' => $this->getCategoriesWithLazyLoadedVisibleChildren(),
            'dropdownMenu' => $dropdownMenu,
            'showImage' => $showImage,
            'children' => $children,
        ]);
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Category\CategoryWithLazyLoadedVisibleChildren[]
     */
    private function getCategoriesWithLazyLoadedVisibleChildren(): array
    {
        if ($this->categoriesWithLazyLoadedVisibleChildren === null) {
            $this->categoriesWithLazyLoadedVisibleChildren = $this->categoryFacade->getCategoriesWithLazyLoadedVisibleAndListableChildrenForParent(
                $this->categoryFacade->getRootCategory(),
                $this->domain->getCurrentDomainConfig()
            );
        }

        return $this->categoriesWithLazyLoadedVisibleChildren;
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
}
