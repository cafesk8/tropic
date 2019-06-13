<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Store;

use Shopsys\ShopBundle\Model\Store\Exception\StoreNotFoundException;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class StoreIdToEntityTransformer implements DataTransformerInterface
{
    /**
     * @var \Shopsys\ShopBundle\Model\Store\StoreFacade
     */
    private $storeFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Store\StoreFacade $storeFacade
     */
    public function __construct(StoreFacade $storeFacade)
    {
        $this->storeFacade = $storeFacade;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Store\Store $store
     * @return int|null
     */
    public function transform($store): ?int
    {
        return $store instanceof Store ? $store->getId() : null;
    }

    /**
     * @param int|null $storeId
     * @return \Shopsys\ShopBundle\Model\Store\Store|null
     */
    public function reverseTransform($storeId): ?Store
    {
        if ($storeId === null) {
            return null;
        }

        try {
            $store = $this->storeFacade->getById((int)$storeId);
        } catch (StoreNotFoundException $notFoundException) {
            throw new TransformationFailedException('Store not found', null, $notFoundException);
        }

        return $store;
    }
}
