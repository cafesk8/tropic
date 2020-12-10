<?php

declare(strict_types=1);

namespace App\Model\Product\Brand;

use App\Model\Category\CategoryBrand\CategoryBrand;
use Doctrine\ORM\Query\Expr\Join;
use Shopsys\FrameworkBundle\Component\Paginator\PaginationResult;
use Shopsys\FrameworkBundle\Component\Paginator\QueryPaginator;
use Shopsys\FrameworkBundle\Component\String\DatabaseSearching;
use Shopsys\FrameworkBundle\Model\Category\Category;
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

    /**
     * @param int[] $ids
     * @return string[]
     */
    public function getSlugsByIds(array $ids): array
    {
        $brands = $this->getBrandRepository()->findBy(['id' => $ids]);

        return array_map(fn (Brand $brand) => $brand->getSlug(), $brands);
    }

    /**
     * @param string[] $slugs
     * @return int[]
     */
    public function getIdsBySlugs(array $slugs): array
    {
        $brands = $this->getBrandRepository()->findBy(['slug' => $slugs]);

        return array_map(fn (Brand $brand) => $brand->getId(), $brands);
    }

    /**
     * @param int[] $brandsIds
     * @param string $locale
     * @param \App\Model\Category\Category|null $category
     * @return \App\Model\Product\Brand\Brand[]
     */
    public function getBrandsForFilterByIds(array $brandsIds, string $locale, ?Category $category = null): array
    {
        $brandsQueryBuilder = $this->getBrandRepository()->createQueryBuilder('b')
            ->select('b, bt')
            ->join('b.translations', 'bt', Join::WITH, 'bt.locale = :locale')
            ->where('b.id IN (:brandIds)')
            ->setParameter('brandIds', $brandsIds)
            ->setParameter('locale', $locale);

        if ($category !== null) {
            $brandsQueryBuilder
                ->leftJoin(CategoryBrand::class, 'cb', Join::WITH, 'b = cb.brand AND cb.category = :category')
                ->setParameter('category', $category)
                ->orderBy('cb.priority, b.name', 'asc');
        } else {
            $brandsQueryBuilder->orderBy('b.name', 'asc');
        }

        return $brandsQueryBuilder->getQuery()->getResult();
    }

    /**
     * @param string $searchText
     * @param int $page
     * @param int $limit
     * @param bool $start
     * @return \Shopsys\FrameworkBundle\Component\Paginator\PaginationResult
     */
    public function getPaginationResultForSearch(
        string $searchText,
        int $page,
        int $limit,
        bool $start
    ): PaginationResult {
        $searchParam = $start ? DatabaseSearching::getLikeSearchString($searchText) . '%' : DatabaseSearching::getFullTextLikeSearchString($searchText);

        $queryBuilder = $this->getBrandRepository()->createQueryBuilder('b')
            ->where('NORMALIZE(b.name) LIKE NORMALIZE(:searchText)')
            ->setParameter('searchText', $searchParam);
        $queryPaginator = new QueryPaginator($queryBuilder);

        return $queryPaginator->getResult($page, $limit);
    }
}
