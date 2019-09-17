<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\AdvancedSearchOrder\Filter;

use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Model\AdvancedSearch\AdvancedSearchFilterInterface;
use Shopsys\ShopBundle\Model\Transport\TransportFacade;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class OrderTransportFilter implements AdvancedSearchFilterInterface
{
    /**
     * @var \Shopsys\ShopBundle\Model\Transport\TransportFacade
     */
    private $transportFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Transport\TransportFacade $transportFacade
     */
    public function __construct(TransportFacade $transportFacade)
    {
        $this->transportFacade = $transportFacade;
    }

    public const NAME = 'orderTransport';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedOperators()
    {
        return [
            self::OPERATOR_IS,
            self::OPERATOR_IS_NOT,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getValueFormType()
    {
        return ChoiceType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getValueFormOptions()
    {
        return [
            'choices' => $this->transportFacade->getAll(),
            'choice_label' => 'name',
            'choice_value' => 'id',
            'multiple' => false,
            'expanded' => false,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function extendQueryBuilder(QueryBuilder $queryBuilder, $rulesData)
    {
        foreach ($rulesData as $index => $ruleData) {
            if ($ruleData->operator === self::OPERATOR_IS || $ruleData->operator === self::OPERATOR_IS_NOT) {
                $dqlOperator = $this->getContainsDqlOperator($ruleData->operator);
                $parameterName = 'orderTransport_' . $index;
                $queryBuilder->andWhere('o.transport ' . $dqlOperator . ' :' . $parameterName);
                $queryBuilder->setParameter($parameterName, $ruleData->value);
            }
        }
    }

    /**
     * @param string $operator
     * @return string
     */
    protected function getContainsDqlOperator($operator)
    {
        switch ($operator) {
            case self::OPERATOR_IS:
                return '=';
            case self::OPERATOR_IS_NOT:
                return '!=';
        }
    }
}
