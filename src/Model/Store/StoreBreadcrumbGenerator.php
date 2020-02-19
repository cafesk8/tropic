<?php

declare(strict_types=1);

namespace App\Model\Store;

use Shopsys\FrameworkBundle\Component\Breadcrumb\BreadcrumbGeneratorInterface;
use Shopsys\FrameworkBundle\Component\Breadcrumb\BreadcrumbItem;

class StoreBreadcrumbGenerator implements BreadcrumbGeneratorInterface
{
    /**
     * @var \App\Model\Store\StoreFacade
     */
    private $storeFacade;

    /**
     * @param \App\Model\Store\StoreFacade $storeFacade
     */
    public function __construct(StoreFacade $storeFacade)
    {
        $this->storeFacade = $storeFacade;
    }

    /**
     * @inheritDoc
     */
    public function getBreadcrumbItems($routeName, array $routeParameters = []): array
    {
        $breadcrumbItems = [];
        $breadcrumbItems[] = new BreadcrumbItem(t('Prodejny'), 'front_store_index');

        $store = $this->storeFacade->getById($routeParameters['storeId']);
        $breadcrumbItems[] = new BreadcrumbItem($store->getName());

        return $breadcrumbItems;
    }

    /**
     * @inheritDoc
     */
    public function getRouteNames(): array
    {
        return ['front_store_detail'];
    }
}