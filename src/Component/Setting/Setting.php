<?php

declare(strict_types=1);

namespace App\Component\Setting;

use Shopsys\FrameworkBundle\Component\Setting\Setting as BaseSetting;

class Setting extends BaseSetting
{
    public const ORDER_TRANSPORT_DEADLINE_HOURS = 'orderTransportDeadlineHours';
    public const ORDER_TRANSPORT_DEADLINE_MINUTES = 'orderTransportDeadlineMinutes';
    public const LOYALTY_PROGRAM_ARTICLE_ID = 'loyaltyProgramArticleId';
    public const PRODUCT_SIZE_ARTICLE_ID = 'productSizeArticleId';
    public const FREE_TRANSPORT_FLAG = 'freeTransportFlagId';
    public const DEFAULT_AVAILABILITY_OUT_OF_STOCK_ID = 'defaultAvailabilityOutOfStockId';
    public const COFIDIS_BANNER_MINIMUM_SHOW_PRICE_ID = 'cofidis_banner_minimum_show_price';
    public const LAST_SENT_M_SERVER_ERROR_500_INFO = 'lastSentMServerError500Info';
    public const LAST_SENT_M_SERVER_ERROR_TIMEOUT_INFO = 'lastSentMServerTimeoutInfo';
    public const ABOUT_US_ARTICLE_ID = 'aboutUsArticleId';
    public const RETURNED_GOODS_ARTICLE_ID = 'returnedGoodsArticleId';
    public const CHANGES_IN_ORDER_ARTICLE_ID = 'changesInOrderArticleId';
    public const COMPLAINT_GOODS_ARTICLE_ID = 'complaintGoodsArticleId';

    /**
     * @param string $key
     * @param int $domainId
     * @return \DateTime|\Shopsys\FrameworkBundle\Component\Money\Money|string|int|float|bool|null
     */
    public function findForDomain($key, $domainId)
    {
        $this->loadDomainValues($domainId);

        if (array_key_exists($key, $this->values[$domainId])) {
            $settingValue = $this->values[$domainId][$key];

            return $settingValue->getValue();
        }

        return null;
    }
}
