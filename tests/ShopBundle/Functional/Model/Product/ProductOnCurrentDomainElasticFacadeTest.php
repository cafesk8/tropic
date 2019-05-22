<?php

namespace Tests\ShopBundle\Functional\Model\Product;

use Shopsys\FrameworkBundle\Model\Product\ProductOnCurrentDomainFacadeInterface;
use Shopsys\ShopBundle\Model\Product\ProductOnCurrentDomainElasticFacade;

class ProductOnCurrentDomainElasticFacadeTest extends ProductOnCurrentDomainFacadeTest
{
    /**
     * @return \Shopsys\FrameworkBundle\Model\Product\ProductOnCurrentDomainFacadeInterface
     */
    public function getProductOnCurrentDomainFacade(): ProductOnCurrentDomainFacadeInterface
    {
        return $this->getContainer()->get(ProductOnCurrentDomainElasticFacade::class);
    }
}
