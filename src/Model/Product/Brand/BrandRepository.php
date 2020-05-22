<?php

declare(strict_types=1);

namespace App\Model\Product\Brand;

use Shopsys\FrameworkBundle\Model\Product\Brand\BrandRepository as BaseBrandRepository;

/**
 * @method \App\Model\Product\Brand\Brand getById(int $brandId)
 * @method \App\Model\Product\Brand\Brand[] getAll()
 */
class BrandRepository extends BaseBrandRepository
{
    /**
     * @param string $name
     * @return \App\Model\Product\Brand\Brand
     */
    public function getByName(string $name): Brand
    {
        /** @var \App\Model\Product\Brand\Brand|null $brand */
        $brand = $this->getBrandRepository()->findOneBy(['name' => $name]);

        if ($brand === null) {
            $message = 'Brand with name ' . $name . ' not found.';
            throw new \Shopsys\FrameworkBundle\Model\Product\Brand\Exception\BrandNotFoundException($message);
        }

        return $brand;
    }
}
