<?php

declare(strict_types=1);

namespace App\Model\Product\Brand;

use App\Model\Product\Search\BrandSearchResult;
use Shopsys\FrameworkBundle\Model\Category\Category;
use Shopsys\FrameworkBundle\Model\Product\Brand\BrandFacade as BaseBrandFacade;

/**
 * @property \App\Component\Image\ImageFacade $imageFacade
 * @property \App\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $em, \App\Model\Product\Brand\BrandRepository $brandRepository, \App\Component\Image\ImageFacade $imageFacade, \App\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade, \Shopsys\FrameworkBundle\Component\Domain\Domain $domain, \Shopsys\FrameworkBundle\Model\Product\Brand\BrandFactoryInterface $brandFactory, \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher)
 * @method \App\Model\Product\Brand\Brand getById(int $brandId)
 * @method \App\Model\Product\Brand\Brand[] getAll()
 * @method dispatchBrandEvent(\App\Model\Product\Brand\Brand $brand, string $eventType)
 * @property \App\Model\Product\Brand\BrandRepository $brandRepository
 * @method \App\Model\Product\Brand\Brand create(\Shopsys\FrameworkBundle\Model\Product\Brand\BrandData $brandData)
 * @method \App\Model\Product\Brand\Brand edit(int $brandId, \Shopsys\FrameworkBundle\Model\Product\Brand\BrandData $brandData)
 */
class BrandFacade extends BaseBrandFacade
{
    /**
     * @param string $name
     * @return \App\Model\Product\Brand\Brand
     */
    public function getByName(string $name): Brand
    {
        return $this->brandRepository->getByName($name);
    }

    /**
     * @param int[] $brandsIds
     * @param string $locale
     * @param \App\Model\Category\Category|null $category
     * @return \App\Model\Product\Brand\Brand[]
     */
    public function getBrandsForFilterByIds(array $brandsIds, string $locale, ?Category $category = null): array
    {
        return $this->brandRepository->getBrandsForFilterByIds($brandsIds, $locale, $category);
    }

    /**
     * @param string $searchText
     * @param int $limit
     * @return \App\Model\Product\Search\BrandSearchResult
     */
    public function getSearchAutocompleteBrands(string $searchText, int $limit): BrandSearchResult
    {
        $firstResult = $this->brandRepository->getPaginationResultForSearch(
            $searchText,
            1,
            $limit,
            true
        );
        $secondResult = $this->brandRepository->getPaginationResultForSearch(
            $searchText,
            1,
            $limit * 2,
            false
        );

        $results = array_merge($firstResult->getResults(), $secondResult->getResults());
        $results = array_unique($results, SORT_REGULAR);
        $results = array_slice($results, 0, $limit);

        return new BrandSearchResult($results, $secondResult->getTotalCount());
    }
}
