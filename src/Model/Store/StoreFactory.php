<?php

declare(strict_types=1);

namespace App\Model\Store;

class StoreFactory
{
    /**
     * @param \App\Model\Store\StoreData $data
     * @return \App\Model\Store\Store
     */
    public function create(StoreData $data): Store
    {
        return Store::create($data);
    }
}
