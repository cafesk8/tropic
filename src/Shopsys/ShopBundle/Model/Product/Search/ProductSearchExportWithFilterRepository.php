<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Search;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\Search\Export\ProductSearchExportWithFilterRepository as BaseProductSearchExportWithFilterRepository;
use Shopsys\ShopBundle\Model\Product\ProductFacade;

class ProductSearchExportWithFilterRepository extends BaseProductSearchExportWithFilterRepository
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository
     */
    protected $parameterRepository;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    protected $productFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository $parameterRepository
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        ParameterRepository $parameterRepository,
        ProductFacade $productFacade
    ) {
        parent::__construct($em, $parameterRepository, $productFacade);
    }

    /**
     * @param int $domainId
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @return array
     */
    protected function extractPrices(int $domainId, Product $product): array
    {
        $prices = [];
        $productSellingPrices = $this->productFacade->getProductSellingPricesIndexedByDomainId($product, $domainId);
        /** @var \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductSellingPrice $productSellingPrice */
        foreach ($productSellingPrices as $productSellingPrice) {
            $prices[] = [
                'pricing_group_id' => $productSellingPrice->getPricingGroup()->getId(),
                'amount' => (float)$productSellingPrice->getSellingPrice()->getPriceWithVat()->getAmount(),
            ];
        }

        return $prices;
    }
}
