<?php

declare(strict_types=1);

namespace App\Model\Heureka;

use App\Component\Domain\DomainHelper;
use Shopsys\Plugin\Cron\SimpleCronModuleInterface;
use SimpleXMLElement;
use Symfony\Bridge\Monolog\Logger;

class HeurekaReviewCronModule implements SimpleCronModuleInterface
{
    public const HEUREKA_REVIEW_XML = 'https://www.heureka.cz/direct/dotaznik/export-review.php?key=acac0c7cfc5fd4e9cf349947bee244e1';
    public const HEUREKA_REVIEW_XML_SK = 'https://www.heureka.sk/direct/dotaznik/export-review.php?key=f7d5c2cfdce008399c2e8cc44c7ae08f';
    public const REVIEW_XML_URLS_BY_DOMAIN_ID = [
        DomainHelper::CZECH_DOMAIN => self::HEUREKA_REVIEW_XML,
        DomainHelper::SLOVAK_DOMAIN => self::HEUREKA_REVIEW_XML_SK,
    ];
    public const MINIMAL_RATING = 4.5;

    private HeurekaReviewFacade $heurekaReviewFacade;

    private HeurekaReviewItemFactory $heurekaReviewItemFactory;

    /**
     * @param \App\Model\Heureka\HeurekaReviewFacade $heurekaReviewFacade
     * @param \App\Model\Heureka\HeurekaReviewItemFactory $heurekaReviewItemFactory
     */
    public function __construct(
        HeurekaReviewFacade $heurekaReviewFacade,
        HeurekaReviewItemFactory $heurekaReviewItemFactory
    ) {
        $this->heurekaReviewFacade = $heurekaReviewFacade;
        $this->heurekaReviewItemFactory = $heurekaReviewItemFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger(Logger $logger)
    {
    }

    public function run()
    {
        foreach (self::REVIEW_XML_URLS_BY_DOMAIN_ID as $domainId => $heurekaReviewUrl) {
            $xml = new SimpleXMLElement($heurekaReviewUrl, LIBXML_NOCDATA, true);

            $heurekaItems = [];

            foreach ($xml as $item) {
                if ((float)$item->total_rating >= self::MINIMAL_RATING) {
                    $heurekaItems[] = $item;
                }

                if (count($heurekaItems) >= HeurekaReview::HEUREKA_REVIEW_LIMIT) {
                    break;
                }
            }

            $heurekaReviews = $this->heurekaReviewFacade->getAll($domainId);
            $latestRatingIds = [];

            foreach ($heurekaReviews as $latestHeurekaReview) {
                $latestRatingIds[] = $latestHeurekaReview->getRatingId();
            }

            foreach ($heurekaItems as $reviewXml) {
                $newHeurekaReviewItem = $this->heurekaReviewItemFactory->create($reviewXml, $domainId);

                if (!in_array($newHeurekaReviewItem->ratingId, $latestRatingIds, true)) {
                    $this->heurekaReviewFacade->create($newHeurekaReviewItem);
                }
            }
        }
    }
}