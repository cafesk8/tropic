<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Store;

class StoreFactory
{
    /**
     * @param \Shopsys\ShopBundle\Model\Store\StoreData $data
     * @return \Shopsys\ShopBundle\Model\Store\Store
     */
    public function create(StoreData $data): Store
    {
        return Store::create($data);
    }
}
