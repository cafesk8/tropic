<?php

declare(strict_types=1);

namespace App\Model\Heureka;

use App\Component\Domain\DomainHelper;
use DateTime;
use SimpleXMLElement;

class HeurekaReviewItemFactory
{
    /**
     * @param \SimpleXMLElement $itemFromXml
     * @param int $domainId
     * @return \App\Model\Heureka\HeurekaReviewItem
     */
    public function create(SimpleXMLElement $itemFromXml, int $domainId): HeurekaReviewItem
    {
        $date = new DateTime();

        return new HeurekaReviewItem(
            $domainId,
            (int)$itemFromXml->rating_id,
            $date->setTimestamp((int)$itemFromXml->unix_timestamp),
            (float)$itemFromXml->total_rating,
            (string)$itemFromXml->name ?? t('Ověřený zákazník', [], 'messages', DomainHelper::LOCALES[$domainId]),
            (string)$itemFromXml->pros,
            (string)$itemFromXml->cons,
            (string)$itemFromXml->summary
        );
    }
}
