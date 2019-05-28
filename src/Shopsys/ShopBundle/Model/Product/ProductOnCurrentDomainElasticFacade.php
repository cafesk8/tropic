<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

use Shopsys\FrameworkBundle\Model\Product\ProductOnCurrentDomainElasticFacade as BaseProductOnCurrentDomainElasticFacade;

class ProductOnCurrentDomainElasticFacade extends BaseProductOnCurrentDomainElasticFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductRepository
     */
    protected $productRepository;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product[] $products
     * @return \Shopsys\FrameworkBundle\Model\Product\Product[]
     */
    public function getVariantsForProducts(array $products): array
    {
        return $this->productRepository->getAllSellableVariantsForMainVariants(
            $products,
            $this->domain->getId(),
            $this->currentCustomer->getPricingGroup()
        );
    }
}
