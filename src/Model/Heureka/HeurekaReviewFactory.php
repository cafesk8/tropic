<?php

declare(strict_types=1);

namespace App\Model\Heureka;

use SimpleXMLElement;

class HeurekaReviewFactory
{
    private HeurekaReviewItemFactory $heurekaReviewItemFactory;

    /**
     * @param \App\Model\Heureka\HeurekaReviewItemFactory $heurekaReviewItemFactory
     */
    public function __construct(HeurekaReviewItemFactory $heurekaReviewItemFactory) {
        $this->heurekaReviewItemFactory = $heurekaReviewItemFactory;
    }

    /**
     * @param \App\Model\Heureka\HeurekaReviewItem $heurekaReviewItem
     * @return \App\Model\Heureka\HeurekaReview
     */
    public function create(HeurekaReviewItem $heurekaReviewItem): HeurekaReview
    {
        return new HeurekaReview($heurekaReviewItem);
    }

    /**
     * @param \SimpleXMLElement $reviewXml
     * @param int $domainId
     * @return \App\Model\Heureka\HeurekaReview
     */
    public function createFromXml(SimpleXMLElement $reviewXml, int $domainId): HeurekaReview
    {
        $heurekaReviewItem = $this->heurekaReviewItemFactory->create($reviewXml, $domainId);

        return $this->create($heurekaReviewItem);
    }
}
