<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Parameter;

use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository as BaseParameterRepository;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;
use Shopsys\ShopBundle\Model\Product\Parameter\Exception\ParameterValueNotFoundException;
use Shopsys\ShopBundle\Model\Product\Product;

class ParameterRepository extends BaseParameterRepository
{
    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter $parameter
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
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
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param string $locale
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getProductParameterValuesByProductSortedByNameQueryBuilder(BaseProduct $product, $locale)
    {
        return parent::getProductParameterValuesByProductSortedByNameQueryBuilder($product, $locale)
            ->andWhere('p.visibleOnFrontend = true');
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
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
     * @return \Shopsys\ShopBundle\Model\Product\Parameter\ParameterValue[]
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
     * @return \Shopsys\ShopBundle\Model\Product\Parameter\ParameterValue
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
