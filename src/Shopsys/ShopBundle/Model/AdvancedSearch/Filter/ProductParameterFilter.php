<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\AdvancedSearch\Filter;

use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Model\AdvancedSearch\AdvancedSearchFilterInterface;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue;
use Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ProductParameterFilter implements AdvancedSearchFilterInterface
{
    public const NAME = 'productParameter';

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade
     */
    private $parameterFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade $parameterFacade
     */
    public function __construct(ParameterFacade $parameterFacade)
    {
        $this->parameterFacade = $parameterFacade;
    }

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
        return ChoiceType::class;
    }

    /**
     * @inheritDoc
     */
    public function getValueFormOptions()
    {
        return [
            'expanded' => false,
            'multiple' => false,
            'choices' => $this->parameterFacade->getAll(),
            'choice_label' => 'name',
            'choice_value' => 'id',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function extendQueryBuilder(QueryBuilder $queryBuilder, $rulesData)
    {
        $isParameter = [];
        $isNotParameter = [];
        foreach ($rulesData as $ruleData) {
            if ($ruleData->operator === self::OPERATOR_IS) {
                $isParameter[] = $ruleData->value;
            } elseif ($ruleData->operator === self::OPERATOR_IS_NOT) {
                $isNotParameter[] = $ruleData->value;
            }
        }
        if (count($isParameter) + count($isNotParameter) > 0) {
            $subQuery = 'SELECT IDENTITY(%s.product) FROM ' . ProductParameterValue::class . ' %1$s WHERE %1$s.parameter IN (:%s)';

            if (count($isParameter) > 0) {
                $queryBuilder->andWhere($queryBuilder->expr()->in('p.id', sprintf($subQuery, 'ppv_is', 'isParameter')));
                $queryBuilder->setParameter('isParameter', $isParameter);
            }
            if (count($isNotParameter) > 0) {
                $queryBuilder->andWhere($queryBuilder->expr()->notIn('p.id', sprintf($subQuery, 'ppv_not', 'isNotParameter')));
                $queryBuilder->setParameter('isNotParameter', $isNotParameter);
            }
        }
    }
}
