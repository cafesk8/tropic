<?php

declare(strict_types=1);

namespace App\Model\Heureka;

use App\Component\Domain\DomainHelper;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Heureka\HeurekaSetting;
use Shopsys\Plugin\Cron\SimpleCronModuleInterface;
use SimpleXMLElement;
use Symfony\Bridge\Monolog\Logger;

class HeurekaReviewCronModule implements SimpleCronModuleInterface
{
    public const HEUREKA_REVIEW_XML_URL_PATTERN = 'https://www.heureka.%s/direct/dotaznik/export-review.php?key=%s';
    public const MINIMAL_RATING = 4.5;

    private HeurekaReviewFacade $heurekaReviewFacade;

    private HeurekaReviewItemFactory $heurekaReviewItemFactory;

    private Domain $domain;

    private HeurekaSetting $heurekaSetting;

    /**
     * @param \App\Model\Heureka\HeurekaReviewFacade $heurekaReviewFacade
     * @param \App\Model\Heureka\HeurekaReviewItemFactory $heurekaReviewItemFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Heureka\HeurekaSetting $heurekaSetting
     */
    public function __construct(
        HeurekaReviewFacade $heurekaReviewFacade,
        HeurekaReviewItemFactory $heurekaReviewItemFactory,
        Domain $domain,
        HeurekaSetting $heurekaSetting
    ) {
        $this->heurekaReviewFacade = $heurekaReviewFacade;
        $this->heurekaReviewItemFactory = $heurekaReviewItemFactory;
        $this->heurekaSetting = $heurekaSetting;
        $this->domain = $domain;
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger(Logger $logger)
    {
    }

    public function run()
    {
        foreach ($this->domain->getAll() as $domainConfig) {
            $domainId = $domainConfig->getId();
            if ($domainId === DomainHelper::ENGLISH_DOMAIN) {
                continue;
            }
            $firstLevelDomain = $this->getFirstLevelDomain($domainId);
            $key = $this->heurekaSetting->getApiKeyByDomainId($domainId);
            $xml = new SimpleXMLElement(sprintf(self::HEUREKA_REVIEW_XML_URL_PATTERN, $firstLevelDomain, $key), LIBXML_NOCDATA, true);

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

    /**
     * @param int $domainId
     * @return string
     */
    private function getFirstLevelDomain(int $domainId): string
    {
        $firstLevelDomain = 'cz';
        if ($domainId === DomainHelper::SLOVAK_DOMAIN) {
            $firstLevelDomain = 'sk';
        }

        return $firstLevelDomain;
    }
}
