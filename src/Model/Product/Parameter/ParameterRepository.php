<?php

declare(strict_types=1);

namespace App\Model\Product\Parameter;

use App\Model\Product\Parameter\Exception\ParameterValueNotFoundException;
use App\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository as BaseParameterRepository;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;

/**
 * @method \App\Model\Product\Parameter\Parameter|null findById(int $parameterId)
 * @method \App\Model\Product\Parameter\Parameter getById(int $parameterId)
 * @method \App\Model\Product\Parameter\Parameter[] getAll()
 * @method \App\Model\Product\Parameter\ParameterValue findOrCreateParameterValueByValueTextAndLocale(string $valueText, string $locale)
 * @method \App\Model\Product\Parameter\ParameterValue getParameterValueByValueTextAndLocale(string $valueText, string $locale)
 * @method \Doctrine\ORM\QueryBuilder getProductParameterValuesByProductQueryBuilder(\App\Model\Product\Product $product)
 * @method \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue[] getProductParameterValuesByProduct(\App\Model\Product\Product $product)
 * @method \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue[] getProductParameterValuesByProductSortedByName(\App\Model\Product\Product $product, string $locale)
 * @method string[][] getParameterValuesIndexedByProductIdAndParameterNameForProducts(\App\Model\Product\Product[] $products, string $locale)
 * @method \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue[] getProductParameterValuesByParameter(\App\Model\Product\Parameter\Parameter $parameter)
 * @method \App\Model\Product\Parameter\Parameter|null findParameterByNames(string[] $namesByLocale)
 */
class ParameterRepository extends BaseParameterRepository
{
    /**
     * @param \App\Model\Product\Parameter\Parameter $parameter
     * @param \App\Model\Product\Product $product
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue|null
     */
    public function findProductParameterValueByParameterAndProduct(Parameter $parameter, Product $product): ?ProductParameterValue
    {
        return $this->getProductParameterValueRepository()->findOneBy([
            'parameter' => $parameter,
            'product' => $product,
        ]);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param string $locale
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getProductParameterValuesByProductSortedByNameQueryBuilder(BaseProduct $product, $locale)
    {
        return parent::getProductParameterValuesByProductSortedByNameQueryBuilder($product, $locale)
            ->andWhere('p.visibleOnFrontend = true');
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param string $locale
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue[]
     */
    public function getAllProductParameterValuesByProductSortedByName(BaseProduct $product, $locale): array
    {
        return parent::getProductParameterValuesByProductSortedByNameQueryBuilder($product, $locale)
            ->getQuery()
            ->execute();
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return \App\Model\Product\Parameter\ParameterValue[]
     */
    public function getParameterValuesBatch(int $limit, int $offset): array
    {
        return $this->getParameterValueRepository()->createQueryBuilder('pv')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->orderBy('pv.id', 'ASC')
            ->getQuery()->getResult();
    }

    /**
     * @param int $id
     * @return \App\Model\Product\Parameter\ParameterValue
     */
    public function getParameterValueById(int $id): ParameterValue
    {
        $parameterValue = $this->getParameterValueRepository()->find($id);

        if ($parameterValue === null) {
            throw new ParameterValueNotFoundException(sprintf('Parameter `%d` was not found', $id));
        }

        return $parameterValue;
    }
}
