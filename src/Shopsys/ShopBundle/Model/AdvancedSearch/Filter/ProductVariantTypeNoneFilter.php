<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\AdvancedSearch\Filter;

use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Model\AdvancedSearch\AdvancedSearchFilterInterface;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class ProductVariantTypeNoneFilter implements AdvancedSearchFilterInterface
{
    public const NAME = 'productVariantTypeNone';

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
    public function extendQueryBuilder(QueryBuilder $queryBuilder, $rulesData): void
    {
        foreach ($rulesData as $index => $ruleData) {
            $parameterName = 'variantType_' . $index;
            if ($ruleData->operator === self::OPERATOR_IS) {
                $queryBuilder->andWhere('p.variantType = :' . $parameterName)
                    ->setParameter($parameterName, Product::VARIANT_TYPE_NONE);
            } else {
                $queryBuilder->andWhere('p.variantType != :' . $parameterName)
                    ->setParameter($parameterName, Product::VARIANT_TYPE_NONE);
            }
        }
    }
}
