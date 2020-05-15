<?php

declare(strict_types=1);

namespace App\Model\Product\Flag;

use Shopsys\FrameworkBundle\Component\EntityExtension\EntityNameResolver;

class ProductFlagFactory
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityNameResolver
     */
    private $entityNameResolver;

    /**
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\EntityNameResolver $entityNameResolver
     */
    public function __construct(EntityNameResolver $entityNameResolver)
    {
        $this->entityNameResolver = $entityNameResolver;
    }

    /**
     * @param \App\Model\Product\Flag\ProductFlagData $productFlagData
     * @return \App\Model\Product\Flag\ProductFlag
     */
    public function create(ProductFlagData $productFlagData): ProductFlag
    {
        $classData = $this->entityNameResolver->resolve(ProductFlag::class);
        /** @var \App\Model\Product\Flag\ProductFlag $productFlag */
        $productFlag = $classData::create($productFlagData);

        return $productFlag;
    }
}
