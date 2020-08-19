<?php

declare(strict_types=1);

namespace App\Model\Heureka;

use Shopsys\Plugin\Cron\SimpleCronModuleInterface;
use SimpleXMLElement;
use Symfony\Bridge\Monolog\Logger;

class HeurekaReviewCronModule implements SimpleCronModuleInterface
{
    public const HEUREKA_REVIEW_XML = 'https://www.heureka.cz/direct/dotaznik/export-review.php?key=acac0c7cfc5fd4e9cf349947bee244e1';
    public const MINIMAL_RATING = 4.5;

    /**
     * @var \App\Model\Heureka\HeurekaReviewFacade
     */
    private $heurekaReviewFacade;

    /**
     * @var \App\Model\Heureka\HeurekaReviewFactory
     */
    private $heurekaReviewFactory;

    /**
     * @param \App\Model\Heureka\HeurekaReviewFacade $heurekaReviewFacade
     * @param \App\Model\Heureka\HeurekaReviewFactory $heurekaReviewFactory
     */
    public function __construct(
        HeurekaReviewFacade $heurekaReviewFacade,
        HeurekaReviewFactory $heurekaReviewFactory
    ) {
        $this->heurekaReviewFacade = $heurekaReviewFacade;
        $this->heurekaReviewFactory = $heurekaReviewFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger(Logger $logger)
    {
    }

    public function run()
    {
        $xml = new SimpleXMLElement(self::HEUREKA_REVIEW_XML, LIBXML_NOCDATA, true);

        $heurekaItems = [];
        foreach($xml as $item) {
            if ((float)$item->total_rating >= floatval(self::MINIMAL_RATING)) {
                $heurekaItems[] = $item;
            }

            if (count($heurekaItems) >= HeurekaReview::HEUREKA_REVIEW_LIMIT) {
                break;
            }
        }

        $heurekaReviews = $this->heurekaReviewFacade->getAll();

        $latestRatingIds = [];
        foreach($heurekaReviews as $latestHeurekaReview) {
            $latestRatingIds[] = $latestHeurekaReview->getRatingId();
        }

        foreach($heurekaItems as $reviews) {
            $newHeurekaReview = $this->heurekaReviewFactory->createFromXml($reviews);
            if (!in_array($newHeurekaReview->getRatingId(), $latestRatingIds, true)) {
                $this->heurekaReviewFacade->create($reviews);
            }
        }
    }
}