<?php

declare(strict_types=1);

namespace App\Model\Feed;

use League\Flysystem\FileNotFoundException;
use Shopsys\FrameworkBundle\Component\Setting\Setting;
use Shopsys\FrameworkBundle\Model\Feed\DailyFeedCronModule as BaseDailyFeedCronModule;

class DailyFeedCronModule extends BaseDailyFeedCronModule
{
    /**
     * {@inheritdoc}
     */
    public function iterate(): bool
    {
        if ($this->getFeedExportCreationDataQueue()->isEmpty()) {
            $this->logger->addDebug('Queue is empty, no feeds to process.');

            return false;
        }

        if ($this->currentFeedExport === null) {
            $this->currentFeedExport = $this->createCurrentFeedExport();
        }

        try {
            $this->currentFeedExport->generateBatch();
        } catch (\Exception $ex) {
            $this->logger->addError($ex->getMessage());
        }

        if ($this->currentFeedExport->isFinished()) {
            $feedInfo = $this->currentFeedExport->getFeedInfo();
            $domainConfig = $this->currentFeedExport->getDomainConfig();

            $this->logger->addDebug(sprintf(
                'Feed "%s" generated on domain "%s" into "%s".',
                $feedInfo->getName(),
                $domainConfig->getName(),
                $this->feedFacade->getFeedFilepath($feedInfo, $domainConfig)
            ));

            if ($this->getFeedExportCreationDataQueue()->next() === true) {
                $this->currentFeedExport = $this->createCurrentFeedExport();
                return true;
            } else {
                $this->currentFeedExport = null;
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function sleep(): void
    {
        $currentFeedName = $this->getFeedExportCreationDataQueue()->getCurrentFeedName();
        $currentDomain = $this->getFeedExportCreationDataQueue()->getCurrentDomain();
        $lastSeekId = $this->currentFeedExport !== null ? $this->currentFeedExport->getLastSeekId() : null;
        $domainIdToContinue = $currentDomain->getId();

        if ($lastSeekId !== null) {
            try {
                $this->currentFeedExport->sleep();
            } catch (FileNotFoundException $exception) {
                $this->logger->addError($exception->getMessage(), [
                    'domainId' => $domainIdToContinue,
                    'feedName' => $currentFeedName,
                    'lastSeekId' => $lastSeekId,
                ]);
                $lastSeekId = null;

                if ($domainIdToContinue === count($this->domain->getAll())) {
                    $domainIdToContinue = null;
                    $currentFeedName = null;
                } else {
                    $domainIdToContinue = $domainIdToContinue + 1;
                }
            }
        }

        $this->setting->set(Setting::FEED_NAME_TO_CONTINUE, $currentFeedName);
        $this->setting->set(Setting::FEED_DOMAIN_ID_TO_CONTINUE, $domainIdToContinue);
        $this->setting->set(Setting::FEED_ITEM_ID_TO_CONTINUE, $lastSeekId);

        $this->logger->addDebug(sprintf(
            'Going to sleep... Will continue with feed "%s" on "%s", processing from ID %d.',
            $currentFeedName,
            $currentDomain->getName(),
            $lastSeekId
        ));
    }
}
