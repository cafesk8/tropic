<?php

declare(strict_types=1);

namespace App\Model\AdvancedSearchOrder\Filter;

use App\Model\Order\Order;
use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Model\AdvancedSearch\AdvancedSearchFilterInterface;
use Shopsys\FrameworkBundle\Model\AdvancedSearch\Exception\AdvancedSearchFilterOperatorNotFoundException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class OrderExportStatusFilter implements AdvancedSearchFilterInterface
{
    public const NAME = 'exportStatus';

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
            'choices' => [
                t('Přeneseno') => Order::EXPORT_SUCCESS,
                t('Zatím nepřeneseno') => Order::EXPORT_NOT_YET,
                t('Chyba při přenosu') => Order::EXPORT_ERROR,
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function extendQueryBuilder(QueryBuilder $queryBuilder, $rulesData)
    {
        foreach ($rulesData as $index => $ruleData) {
            if ($ruleData->operator === self::OPERATOR_IS || $ruleData->operator === self::OPERATOR_IS_NOT) {
                $dqlOperator = $this->getContainsDqlOperator($ruleData->operator);
                $parameterName = 'orderExportStatus_' . $index;
                $queryBuilder->andWhere('o.exportStatus ' . $dqlOperator . ' :' . $parameterName);
                $queryBuilder->setParameter($parameterName, $ruleData->value);
            }
        }
    }

    /**
     * @param string $operator
     * @return string
     */
    protected function getContainsDqlOperator(string $operator): string
    {
        switch ($operator) {
            case self::OPERATOR_IS:
                return '=';
            case self::OPERATOR_IS_NOT:
                return '!=';
        }

        throw new AdvancedSearchFilterOperatorNotFoundException($operator);
    }
}