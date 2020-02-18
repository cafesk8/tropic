<?php

declare(strict_types=1);

namespace Tests\App\Functional\Model\Unit;

use App\DataFixtures\Demo\ProductDataFixture;
use App\DataFixtures\Demo\UnitDataFixture;
use Shopsys\FrameworkBundle\Model\Product\ProductDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\ProductFacade;
use Shopsys\FrameworkBundle\Model\Product\Unit\UnitData;
use Shopsys\FrameworkBundle\Model\Product\Unit\UnitFacade;
use Tests\App\Test\TransactionFunctionalTestCase;

class UnitFacadeTest extends TransactionFunctionalTestCase
{
    public function testDeleteByIdAndReplace()
    {
        $em = $this->getEntityManager();
        /** @var \Shopsys\FrameworkBundle\Model\Product\Unit\UnitFacade $unitFacade */
        $unitFacade = $this->getContainer()->get(UnitFacade::class);
        /** @var \App\Model\Product\ProductDataFactory $productDataFactory */
        $productDataFactory = $this->getContainer()->get(ProductDataFactoryInterface::class);
        /** @var \Shopsys\FrameworkBundle\Model\Product\ProductFacade $productFacade */
        $productFacade = $this->getContainer()->get(ProductFacade::class);

        $unitData = new UnitData();
        $unitData->name = ['cs' => 'name'];
        $unitToDelete = $unitFacade->create($unitData);
        /** @var \Shopsys\FrameworkBundle\Model\Product\Unit\Unit $unitToReplaceWith */
        $unitToReplaceWith = $this->getReference(UnitDataFixture::UNIT_PIECES);
        /** @var \App\Model\Product\Product $product */
        $product = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '1');
        /** @var \App\Model\Product\ProductData $productData */
        $productData = $productDataFactory->createFromProduct($product);

        $productData->unit = $unitToDelete;
        $productFacade->edit($product->getId(), $productData);

        $unitFacade->deleteById($unitToDelete->getId(), $unitToReplaceWith->getId());

        $em->refresh($product);

        $this->assertEquals($unitToReplaceWith, $product->getUnit());
    }
}
