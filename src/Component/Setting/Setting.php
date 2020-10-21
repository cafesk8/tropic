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
}
