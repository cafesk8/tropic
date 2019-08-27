<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Setting;

use Shopsys\FrameworkBundle\Component\Setting\Setting as BaseSetting;

class Setting extends BaseSetting
{
    public const ORDER_TRANSPORT_DEADLINE_HOURS = 'orderTransportDeadlineHours';
    public const ORDER_TRANSPORT_DEADLINE_MINUTES = 'orderTransportDeadlineMinutes';
    public const BUSHMAN_CLUB_ARTICLE_ID = 'bushmanClubArticleId';
    public const PRODUCT_SIZE_ARTICLE_ID = 'productSizeArticleId';
    public const OUR_VALUES_ARTICLE_ID = 'ourValuesArticleId';
    public const OUR_STORY_ARTICLE_ID = 'ourStoryArticleId';
}
