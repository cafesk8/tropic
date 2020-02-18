<?php

declare(strict_types=1);

namespace App\Model\Store;

use App\Model\Store\Exception\StoreNotFoundException;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class StoreIdToEntityTransformer implements DataTransformerInterface
{
    /**
     * @var \App\Model\Store\StoreFacade
     */
    private $storeFacade;

    /**
     * @param \App\Model\Store\StoreFacade $storeFacade
     */
    public function __construct(StoreFacade $storeFacade)
    {
        $this->storeFacade = $storeFacade;
    }

    /**
     * @param \App\Model\Store\Store|null $store
     * @return int|null
     */
    public function transform($store): ?int
    {
        return $store instanceof Store ? $store->getId() : null;
    }

    /**
     * @param int|null $storeId
     * @return \App\Model\Store\Store|null
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
