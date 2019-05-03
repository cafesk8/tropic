<?php

namespace Shopsys\ShopBundle\Controller\Front;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Category\CategoryFacade;
use Shopsys\FrameworkBundle\Model\Category\TopCategory\TopCategoryFacade;
use Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer;
use Shopsys\ShopBundle\Model\Category\HorizontalCategoryFacade;

class CategoryController extends FrontBaseController
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Category\CategoryFacade
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
     * @var \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer
     */
    private $currentCustomer;

    /**
     * @var \Shopsys\ShopBundle\Model\Category\HorizontalCategoryFacade
     */
    private $horizontalCategoryFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Category\CategoryFacade $categoryFacade
     * @param \Shopsys\FrameworkBundle\Model\Category\TopCategory\TopCategoryFacade $topCategoryFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer $currentCustomer
     * @param \Shopsys\ShopBundle\Model\Category\HorizontalCategoryFacade $horizontalCategoryFacade
     */
    public function __construct(
        Domain $domain,
        CategoryFacade $categoryFacade,
        TopCategoryFacade $topCategoryFacade,
        CurrentCustomer $currentCustomer,
        HorizontalCategoryFacade $horizontalCategoryFacade
    ) {
        $this->domain = $domain;
        $this->categoryFacade = $categoryFacade;
        $this->topCategoryFacade = $topCategoryFacade;
        $this->currentCustomer = $currentCustomer;
        $this->horizontalCategoryFacade = $horizontalCategoryFacade;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function hoverMenuAction()
    {
        $categoriesWithLazyLoadedVisibleChildren = $this->categoryFacade->getCategoriesWithLazyLoadedVisibleChildrenForParent(
            $this->categoryFacade->getRootCategory(),
            $this->domain->getCurrentDomainConfig()
        );

        return $this->render('@ShopsysShop/Front/Content/Category/hoverMenu.html.twig', [
            'categoriesWithLazyLoadedVisibleChildren' => $categoriesWithLazyLoadedVisibleChildren,
        ]);
    }

    public function topAction()
    {
        return $this->render('@ShopsysShop/Front/Content/Category/top.html.twig', [
            'categories' => $this->topCategoryFacade->getVisibleCategoriesByDomainId($this->domain->getId()),
        ]);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Category\Category[] $categories
     * @param bool $showProductsCountByCategory
     */
    public function categoryListAction(array $categories, $showProductsCountByCategory = true)
    {
        if ($showProductsCountByCategory === true) {
            $pricingGroup = $this->currentCustomer->getPricingGroup();
            $domainId = $this->domain->getId();

            $listableProductCountsIndexedByCategoryId = $this->categoryFacade
                ->getListableProductCountsIndexedByCategoryId($categories, $pricingGroup, $domainId);
        } else {
            $listableProductCountsIndexedByCategoryId = [];
        }

        return $this->render('@ShopsysShop/Front/Content/Category/categoryList.html.twig', [
            'categories' => $categories,
            'listableProductCountsIndexedByCategoryId' => $listableProductCountsIndexedByCategoryId,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function horizontalMenuAction()
    {
        $categories = $this->horizontalCategoryFacade->getCategoriesForHorizontalMenuOnCurrentDomain();

        return $this->render('@ShopsysShop/Front/Inline/Category/horizontalMenu.html.twig', [
            'categories' => $categories,
        ]);
    }
}
