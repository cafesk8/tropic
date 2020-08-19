<?php

declare(strict_types=1);

namespace App\Model\Heureka;

use SimpleXMLElement;

class HeurekaReviewFactory
{
    /**
     * @var \App\Model\Heureka\HeurekaReviewItemFactory
     */
    private $heurekaReviewItemFactory;

    /**
     * @param \App\Model\Heureka\HeurekaReviewItemFactory $heurekaReviewItemFactory
     */
    public function __construct(
        HeurekaReviewItemFactory $heurekaReviewItemFactory
    ) {
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
     * @param \SimpleXMLElement $simpleXmlElement
     * @return \App\Model\Heureka\HeurekaReview
     */
    public function createFromXml(SimpleXMLElement $simpleXmlElement): HeurekaReview
    {
        $heurekaReviewItem = $this->heurekaReviewItemFactory->create($simpleXmlElement);
        $heurekaReview = $this->create($heurekaReviewItem);

        return $heurekaReview;
    }
}
