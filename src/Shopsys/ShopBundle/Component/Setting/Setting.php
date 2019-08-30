<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Setting;

use Shopsys\FrameworkBundle\Component\Setting\Setting as BaseSetting;

class Setting extends BaseSetting
{
    public const ORDER_TRANSPORT_DEADLINE_HOURS = 'orderTransportDeadlineHours';
    public const ORDER_TRANSPORT_DEADLINE_MINUTES = 'orderTransportDeadlineMinutes';
    public const BUSHMAN_CLUB_ARTICLE_ID = 'bushmanClubArticleId';
}
