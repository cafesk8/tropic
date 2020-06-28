<?php

declare(strict_types=1);

namespace App\Model\Category;

use Shopsys\FrameworkBundle\Component\Breadcrumb\BreadcrumbGeneratorInterface;
use Shopsys\FrameworkBundle\Component\Breadcrumb\BreadcrumbItem;

class SaleCategoryBreadcrumbGenerator implements BreadcrumbGeneratorInterface
{
    /**
     * @var \App\Model\Category\CategoryRepository
     */
    private $categoryRepository;

    /**
     * @var \App\Model\Category\CategoryFacade
     */
    private $categoryFacade;

    /**
     * @param \App\Model\Category\CategoryRepository $categoryRepository
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        CategoryFacade $categoryFacade
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->categoryFacade = $categoryFacade;
    }

    /**
     * @param string $routeName
     * @param array $routeParameters
     * @return \Shopsys\FrameworkBundle\Component\Breadcrumb\BreadcrumbItem[]
     */
    public function getBreadcrumbItems($routeName, array $routeParameters = []): array
    {
        $currentCategory = $this->categoryFacade->getSaleCategoryByFriendlyUrl($routeParameters['friendlyUrl']);

        $saleRootCategory = $this->categoryRepository->findByType(Category::SALE_TYPE);

        $breadcrumbItems = [];

        if ($saleRootCategory !== null) {
            $breadcrumbItems[] = new BreadcrumbItem(
                $saleRootCategory->getName(),
                'front_product_list',
                ['id' => $saleRootCategory->getId()]
            );
        }

        $breadcrumbItems[] = new BreadcrumbItem(
            $currentCategory->getName()
        );

        return $breadcrumbItems;
    }

    /**
     * @return string[]
     */
    public function getRouteNames(): array
    {
        return ['front_sale_product_list'];
    }
}
