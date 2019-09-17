<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Model\Order\MassAction;

use Doctrine\ORM\QueryBuilder;

class OrderMassActionFacade
{
    /**
     * @param \Shopsys\ShopBundle\Model\Order\MassAction\OrderMassActionData $orderMassActionData
     * @param \Doctrine\ORM\QueryBuilder $selectQueryBuilder
     * @param array $checkedProductIds
     * @return int[]
     */
    public function getOrdersIdsForMassAction(
        OrderMassActionData $orderMassActionData,
        QueryBuilder $selectQueryBuilder,
        array $checkedProductIds
    ): array {
        $selectedProductIds = [];

        if ($orderMassActionData->selectType === OrderMassActionData::SELECT_TYPE_ALL_RESULTS) {
            $queryBuilder = clone $selectQueryBuilder;

            $results = $queryBuilder
                ->select('o.id')
                ->getQuery()
                ->getScalarResult();

            foreach ($results as $result) {
                $selectedProductIds[] = $result['id'];
            }
        } elseif ($orderMassActionData->selectType === OrderMassActionData::SELECT_TYPE_CHECKED) {
            $selectedProductIds = $checkedProductIds;
        } else {
            throw new \Shopsys\FrameworkBundle\Model\Product\MassAction\Exception\UnsupportedSelectionType($orderMassActionData->selectType);
        }

        return $selectedProductIds;
    }
}
