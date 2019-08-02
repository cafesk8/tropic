<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\LastVisitedProducts;

use Shopsys\ShopBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Product\ProductFacade;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;

class LastVisitedProductsFacade
{
    public const COOKIE_PRODUCT_IDS_DELIMITER = ',';

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    private $requestStack;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
     */
    public function __construct(ProductFacade $productFacade, RequestStack $requestStack)
    {
        $this->productFacade = $productFacade;
        $this->requestStack = $requestStack;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\ParameterBag $cookies
     * @param int $limit
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public function getProductsFromCookieSortedByNewest(ParameterBag $cookies, int $limit): array
    {
        $productIds = $this->getProductIdsFromCookieWithoutCurrentProduct($cookies, $limit);

        $products = $this->productFacade->getAllVisibleByIds($productIds);

        return $this->sortProductsInSameOrderAsIdsFromCookie($products, $productIds);
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
     * @param \Shopsys\ShopBundle\Model\Product\Product[] $products
     * @param int[] $productIds
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    private function sortProductsInSameOrderAsIdsFromCookie(array $products, array $productIds): array
    {
        usort($products, function (Product $product1, Product $product2) use ($productIds) {
            $product1Priority = array_search($product1->getId(), $productIds, true);
            $product2Priority = array_search($product2->getId(), $productIds, true);

            return $product1Priority < $product2Priority ? -1 : 1;
        });

        return $products;
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
