<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Parameter;

use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository as BaseParameterRepository;
use Shopsys\ShopBundle\Model\Product\Product;

class ParameterRepository extends BaseParameterRepository
{
    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter $parameter
     * @param string $locale
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue|null
     */
    public function findProductParameterValueByProductAndParameterAndLocale(Product $product, Parameter $parameter, string $locale)
    {
        $queryBuilder = $this->getProductParameterValueRepository()->createQueryBuilder('ppv')
            ->join('ppv.parameter', 'ppvp')
            ->join('ppv.value', 'ppvv')
            ->where('ppv.parameter = :parameter')
            ->andWhere('ppv.product = :product')
            ->andWhere('ppvv.locale = :locale')
            ->setParameters([
                'parameter' => $parameter,
                'product' => $product,
                'locale' => $locale,
            ]);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
