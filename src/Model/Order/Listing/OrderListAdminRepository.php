<?php

declare(strict_types=1);

namespace App\Model\Order\Listing;

use Shopsys\FrameworkBundle\Model\Order\Listing\OrderListAdminRepository as BaseOrderListAdminRepository;

class OrderListAdminRepository extends BaseOrderListAdminRepository
{
    /**
     * @param string $locale
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getOrderListQueryBuilder($locale)
    {
        return parent::getOrderListQueryBuilder($locale)
            ->select('
                o.id,
                o.number,
                o.domainId,
                o.createdAt,
                MAX(ost.name) AS statusName,
                o.totalPriceWithVat,
                CONCAT(o.lastName, \' \', o.firstName) AS customerName,
                o.goPayStatus'
            );
    }
}