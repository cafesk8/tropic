<?php

declare(strict_types=1);

namespace App\Model\Category;

use Shopsys\FrameworkBundle\Component\Breadcrumb\BreadcrumbGeneratorInterface;
use Shopsys\FrameworkBundle\Component\Breadcrumb\BreadcrumbItem;

class NewsCategoryBreadcrumbGenerator implements BreadcrumbGeneratorInterface
{
    private CategoryRepository $categoryRepository;

    private CategoryFacade $categoryFacade;

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
        $currentCategory = $this->categoryFacade->getById($routeParameters['id']);

        $newsRootCategory = $this->categoryRepository->findByType(Category::NEWS_TYPE);

        $breadcrumbItems = [];

        if ($newsRootCategory !== null) {
            $breadcrumbItems[] = new BreadcrumbItem(
                $newsRootCategory->getName(),
                'front_product_list',
                ['id' => $newsRootCategory->getId()]
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
        return ['front_news_product_list'];
    }
}
