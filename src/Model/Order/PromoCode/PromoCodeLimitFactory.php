<?php

declare(strict_types=1);

namespace App\Model\Order\PromoCode;

use Shopsys\FrameworkBundle\Component\EntityExtension\EntityNameResolver;

class PromoCodeLimitFactory
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityNameResolver
     */
    protected $entityNameResolver;

    /**
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\EntityNameResolver $entityNameResolver
     */
    public function __construct(EntityNameResolver $entityNameResolver)
    {
        $this->entityNameResolver = $entityNameResolver;
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCodeLimitData $data
     * @return \App\Model\Order\PromoCode\PromoCodeLimit
     */
    public function create(PromoCodeLimitData $data): PromoCodeLimit
    {
        $classData = $this->entityNameResolver->resolve(PromoCodeLimit::class);

        return new $classData($data);
    }
}
