<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\PromoCode;

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
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeLimitData $data
     * @return \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeLimit
     */
    public function create(PromoCodeLimitData $data): PromoCodeLimit
    {
        $classData = $this->entityNameResolver->resolve(PromoCodeLimit::class);

        return new $classData($data);
    }
}
