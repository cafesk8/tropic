<?php

declare(strict_types=1);

namespace Tests\App\Functional\Model\Product;

use App\Model\Product\ProductOnCurrentDomainElasticFacade;
use Shopsys\FrameworkBundle\Model\Product\ProductOnCurrentDomainFacadeInterface;

class ProductOnCurrentDomainElasticFacadeCountDataTest extends ProductOnCurrentDomainFacadeCountDataTest
{
    /**
     * @return \Shopsys\FrameworkBundle\Model\Product\ProductOnCurrentDomainFacadeInterface
     */
    public function getProductOnCurrentDomainFacade(): ProductOnCurrentDomainFacadeInterface
    {
        return $this->getContainer()->get(ProductOnCurrentDomainElasticFacade::class);
    }
}
