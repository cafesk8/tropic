<?php

declare(strict_types=1);

namespace App\Model\Heureka;

use DateTime;
use SimpleXMLElement;

class HeurekaReviewItemFactory
{
    public const DEFAULT_REVIEWER_NAME = 'Ověřený zákazník';

    /**
     * @param \SimpleXMLElement $itemFromXml
     * @return \App\Model\Heureka\HeurekaReviewItem
     */
    public function create(SimpleXMLElement $itemFromXml): HeurekaReviewItem
    {
        $date = new DateTime();

        return new HeurekaReviewItem(
            (int)$itemFromXml->rating_id,
            $date->setTimestamp((int)$itemFromXml->unix_timestamp),
            (float)$itemFromXml->total_rating,
            (string)$itemFromXml->name ?? self::DEFAULT_REVIEWER_NAME,
            (string)$itemFromXml->pros,
            (string)$itemFromXml->cons,
            (string)$itemFromXml->summary
        );
    }
}
