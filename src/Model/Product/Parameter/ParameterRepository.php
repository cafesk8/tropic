<?php

declare(strict_types=1);

namespace App\Model\Product\Parameter;

use App\Model\Product\Parameter\Exception\ParameterValueNotFoundException;
use App\Model\Product\Product;
use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository as BaseParameterRepository;

/**
 * @method \App\Model\Product\Parameter\Parameter|null findById(int $parameterId)
 * @method \App\Model\Product\Parameter\Parameter getById(int $parameterId)
 * @method \App\Model\Product\Parameter\Parameter[] getAll()
 * @method \App\Model\Product\Parameter\ParameterValue findOrCreateParameterValueByValueTextAndLocale(string $valueText, string $locale)
 * @method \App\Model\Product\Parameter\ParameterValue getParameterValueByValueTextAndLocale(string $valueText, string $locale)
 * @method \App\Model\Product\Parameter\ProductParameterValue[] getProductParameterValuesByProduct(\App\Model\Product\Product $product)
 * @method \App\Model\Product\Parameter\ProductParameterValue[] getProductParameterValuesByProductSortedByName(\App\Model\Product\Product $product, string $locale)
 * @method string[][] getParameterValuesIndexedByProductIdAndParameterNameForProducts(\App\Model\Product\Product[] $products, string $locale)
 * @method \App\Model\Product\Parameter\ProductParameterValue[] getProductParameterValuesByParameter(\App\Model\Product\Parameter\Parameter $parameter)
 * @method \App\Model\Product\Parameter\Parameter|null findParameterByNames(string[] $namesByLocale)
 * @method \Doctrine\ORM\QueryBuilder getProductParameterValuesByProductQueryBuilder(\App\Model\Product\Product $product)
 * @method \Doctrine\ORM\QueryBuilder getProductParameterValuesByProductSortedByNameQueryBuilder(\App\Model\Product\Product $product, string $locale)
 */
class ParameterRepository extends BaseParameterRepository
{
    /**
     * @param \App\Model\Product\Parameter\Parameter $parameter
     * @param \App\Model\Product\Product $product
     * @return \App\Model\Product\Parameter\ProductParameterValue|null
     */
    public function findProductParameterValueByParameterAndProduct(Parameter $parameter, Product $product): ?ProductParameterValue
    {
        return $this->getProductParameterValueRepository()->findOneBy([
            'parameter' => $parameter,
            'product' => $product,
        ]);
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

    /**
     * @param \App\Model\Product\Product $product
     * @param string $locale
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getProductParameterValuesByProductSortedByPositionQueryBuilder(Product $product, $locale): QueryBuilder
    {
        $queryBuilder = $this->em->createQueryBuilder()
            ->select('ppv')
            ->from(ProductParameterValue::class, 'ppv')
            ->join('ppv.parameter', 'p')
            ->join('p.translations', 'pt')
            ->where('ppv.product = :product_id')
            ->andWhere('pt.locale = :locale')
            ->setParameters([
                'product_id' => $product->getId(),
                'locale' => $locale,
            ])
            ->orderBy('ppv.position');

        return $queryBuilder;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param string $locale
     * @return \App\Model\Product\Parameter\ProductParameterValue[]
     */
    public function getProductParameterValuesByProductSortedByPosition(Product $product, $locale): array
    {
        $queryBuilder = $this->getProductParameterValuesByProductSortedByPositionQueryBuilder($product, $locale);

        return $queryBuilder->getQuery()->execute();
    }
}
