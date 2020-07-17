<?php

declare(strict_types=1);

namespace App\Component\Router\PrettyFilterUrl;

use App\Model\Product\Brand\BrandRepository;

class PrettyFilterUrlFacade
{
    private BrandRepository $brandRepository;

    /**
     * @param \App\Model\Product\Brand\BrandRepository $brandRepository
     */
    public function __construct(BrandRepository $brandRepository)
    {
        $this->brandRepository = $brandRepository;
    }

    /**
     * @param int[] $ids
     * @return string[]
     */
    public function getBrandSlugsByIds(array $ids): array
    {
        return $this->brandRepository->getSlugsByIds($ids);
    }

    /**
     * @param string[] $slugs
     * @return int[]
     */
    public function getBrandIdsBySlugs(array $slugs): array
    {
        return $this->brandRepository->getIdsBySlugs($slugs);
    }
}
