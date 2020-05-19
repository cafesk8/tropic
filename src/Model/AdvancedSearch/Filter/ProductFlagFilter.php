<?php

declare(strict_types=1);

namespace App\Model\AdvancedSearch\Filter;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Model\AdvancedSearch\Filter\ProductFlagFilter as BaseProductFlagFilter;
use Shopsys\FrameworkBundle\Model\Product\Product;

/**
 * @method __construct(\App\Model\Product\Flag\FlagFacade $flagFacade)
 */
class ProductFlagFilter extends BaseProductFlagFilter
{
    /**
     * @var \App\Model\Product\Flag\FlagFacade
     */
    protected $flagFacade;

    /**
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param mixed $rulesData
     */
    public function extendQueryBuilder(QueryBuilder $queryBuilder, $rulesData)
    {
        $isNotFlags = [];

        foreach ($rulesData as $index => $ruleData) {
            if ($ruleData->operator === self::OPERATOR_IS) {
                $tableAlias = 'pf' . $index;
                $flagTableAlias = 'f' . $index;
                $flagParameter = 'flag' . $index;
                $queryBuilder->join('p.flags', $tableAlias);
                $queryBuilder->join($tableAlias . '.flag', $flagTableAlias, Join::WITH, $flagTableAlias . '.id = :' . $flagParameter);
                $queryBuilder->setParameter($flagParameter, $ruleData->value);
            } elseif ($ruleData->operator === self::OPERATOR_IS_NOT) {
                $isNotFlags[] = $ruleData->value;
            }
        }

        if (count($isNotFlags) > 0) {
            $subQuery = 'SELECT flag_p.id FROM ' . Product::class . ' flag_p JOIN flag_p.flags _pf JOIN _pf.flag _f WITH _f.id IN (:isNotFlags)';
            $queryBuilder->andWhere('p.id NOT IN (' . $subQuery . ')');
            $queryBuilder->setParameter('isNotFlags', $isNotFlags);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getValueFormOptions()
    {
        return [
            'expanded' => false,
            'multiple' => false,
            'choices' => $this->flagFacade->getAllExceptFreeTransportFlag(),
            'choice_label' => 'name',
            'choice_value' => 'id',
        ];
    }
}
