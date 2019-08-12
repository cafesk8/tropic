<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\AdvancedSearch\Filter;

use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Model\AdvancedSearch\AdvancedSearchFilterInterface;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class ProductMainVariantFilter implements AdvancedSearchFilterInterface
{
    public const NAME = 'productMainVariant';

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @inheritDoc
     */
    public function getAllowedOperators()
    {
        return [
            self::OPERATOR_IS,
            self::OPERATOR_IS_NOT,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getValueFormType()
    {
        return HiddenType::class;
    }

    /**
     * @inheritDoc
     */
    public function getValueFormOptions()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function extendQueryBuilder(QueryBuilder $queryBuilder, $rulesData)
    {
        foreach ($rulesData as $index => $ruleData) {
            if ($ruleData->operator === self::OPERATOR_IS) {
                $parameterName = 'variantType_' . $index;
                $queryBuilder->andWhere('p.variantType = :' . $parameterName)
                    ->setParameter($parameterName, Product::VARIANT_TYPE_MAIN);
            }
        }
    }
}
