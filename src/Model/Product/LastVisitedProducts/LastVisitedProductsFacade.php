<?php

declare(strict_types=1);

namespace App\Model\Product\LastVisitedProducts;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;

class LastVisitedProductsFacade
{
    public const COOKIE_PRODUCT_IDS_DELIMITER = ',';

    /**
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    private $requestStack;

    /**
     * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\ParameterBag $cookies
     * @param int $limit
     * @return int[]
     */
    public function getProductIdsFromCookieSortedByNewest(ParameterBag $cookies, int $limit): array
    {
        return $this->getProductIdsFromCookieWithoutCurrentProduct($cookies, $limit);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\ParameterBag $cookies
     * @param int $limit
     * @return int[]
     */
    private function getProductIdsFromCookieWithoutCurrentProduct(ParameterBag $cookies, int $limit): array
    {
        $currentProductId = $this->getCurrentProductId();
        $productIdsCookie = $cookies->get(ProductDetailVisitListener::LAST_VISITED_PRODUCTS_COOKIE, '');
        $productIds = explode(self::COOKIE_PRODUCT_IDS_DELIMITER, $productIdsCookie);
        $productIds = array_map('intval', $productIds);

        $foundProductIdKey = array_search($currentProductId, $productIds, true);

        if ($currentProductId !== null && $foundProductIdKey !== false) {
            unset($productIds[$foundProductIdKey]);
        }

        return array_slice($productIds, 0, $limit, true);
    }

    /**
     * @return int|null
     */
    private function getCurrentProductId(): ?int
    {
        $currentProductId = null;
        $route = $this->requestStack->getMasterRequest()->attributes->get('_route');

        if ($route === 'front_product_detail') {
            $currentProductId = $this->requestStack->getMasterRequest()->get('id');
        }

        return $currentProductId;
    }
}
