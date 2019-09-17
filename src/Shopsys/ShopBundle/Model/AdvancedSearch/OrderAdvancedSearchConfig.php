<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\AdvancedSearch;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\AdvancedSearch\OrderAdvancedSearchConfig as BaseOrderAdvancedSearchConfig;
use Shopsys\FrameworkBundle\Model\AdvancedSearchOrder\Filter\OrderCityFilter;
use Shopsys\FrameworkBundle\Model\AdvancedSearchOrder\Filter\OrderCreateDateFilter;
use Shopsys\FrameworkBundle\Model\AdvancedSearchOrder\Filter\OrderDomainFilter;
use Shopsys\FrameworkBundle\Model\AdvancedSearchOrder\Filter\OrderEmailFilter;
use Shopsys\FrameworkBundle\Model\AdvancedSearchOrder\Filter\OrderLastNameFilter;
use Shopsys\FrameworkBundle\Model\AdvancedSearchOrder\Filter\OrderNameFilter;
use Shopsys\FrameworkBundle\Model\AdvancedSearchOrder\Filter\OrderNumberFilter;
use Shopsys\FrameworkBundle\Model\AdvancedSearchOrder\Filter\OrderPhoneNumberFilter;
use Shopsys\FrameworkBundle\Model\AdvancedSearchOrder\Filter\OrderPriceFilterWithVatFilter;
use Shopsys\FrameworkBundle\Model\AdvancedSearchOrder\Filter\OrderProductFilter;
use Shopsys\FrameworkBundle\Model\AdvancedSearchOrder\Filter\OrderStatusFilter;
use Shopsys\FrameworkBundle\Model\AdvancedSearchOrder\Filter\OrderStreetFilter;
use Shopsys\ShopBundle\Model\AdvancedSearchOrder\Filter\OrderTransportFilter;

class OrderAdvancedSearchConfig extends BaseOrderAdvancedSearchConfig
{
    /**
     * @param \Shopsys\FrameworkBundle\Model\AdvancedSearchOrder\Filter\OrderNumberFilter $orderNumberFilter
     * @param \Shopsys\FrameworkBundle\Model\AdvancedSearchOrder\Filter\OrderCreateDateFilter $orderCreateDateFilter
     * @param \Shopsys\FrameworkBundle\Model\AdvancedSearchOrder\Filter\OrderPriceFilterWithVatFilter $orderPriceFilterWithVatFilter
     * @param \Shopsys\FrameworkBundle\Model\AdvancedSearchOrder\Filter\OrderDomainFilter $orderDomainFilter
     * @param \Shopsys\FrameworkBundle\Model\AdvancedSearchOrder\Filter\OrderStatusFilter $orderStatusFilter
     * @param \Shopsys\FrameworkBundle\Model\AdvancedSearchOrder\Filter\OrderProductFilter $orderProductFilter
     * @param \Shopsys\FrameworkBundle\Model\AdvancedSearchOrder\Filter\OrderPhoneNumberFilter $orderPhoneNumberFilter
     * @param \Shopsys\FrameworkBundle\Model\AdvancedSearchOrder\Filter\OrderStreetFilter $orderStreetFilter
     * @param \Shopsys\FrameworkBundle\Model\AdvancedSearchOrder\Filter\OrderNameFilter $orderNameFilter
     * @param \Shopsys\FrameworkBundle\Model\AdvancedSearchOrder\Filter\OrderLastNameFilter $orderLastNameFilter
     * @param \Shopsys\FrameworkBundle\Model\AdvancedSearchOrder\Filter\OrderEmailFilter $orderEmailFilter
     * @param \Shopsys\FrameworkBundle\Model\AdvancedSearchOrder\Filter\OrderCityFilter $orderCityFilter
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\ShopBundle\Model\AdvancedSearchOrder\Filter\OrderTransportFilter $orderTransportFilter
     */
    public function __construct(
        OrderNumberFilter $orderNumberFilter,
        OrderCreateDateFilter $orderCreateDateFilter,
        OrderPriceFilterWithVatFilter $orderPriceFilterWithVatFilter,
        OrderDomainFilter $orderDomainFilter,
        OrderStatusFilter $orderStatusFilter,
        OrderProductFilter $orderProductFilter,
        OrderPhoneNumberFilter $orderPhoneNumberFilter,
        OrderStreetFilter $orderStreetFilter,
        OrderNameFilter $orderNameFilter,
        OrderLastNameFilter $orderLastNameFilter,
        OrderEmailFilter $orderEmailFilter,
        OrderCityFilter $orderCityFilter,
        Domain $domain,
        OrderTransportFilter $orderTransportFilter
    ) {
        parent::__construct(
            $orderNumberFilter,
            $orderCreateDateFilter,
            $orderPriceFilterWithVatFilter,
            $orderDomainFilter,
            $orderStatusFilter,
            $orderProductFilter,
            $orderPhoneNumberFilter,
            $orderStreetFilter,
            $orderNameFilter,
            $orderLastNameFilter,
            $orderEmailFilter,
            $orderCityFilter,
            $domain
        );

        $this->registerFilter($orderTransportFilter);
    }
}
