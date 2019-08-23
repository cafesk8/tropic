<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Feed;

use Shopsys\FrameworkBundle\Model\Feed\DailyFeedCronModule as BaseDailyFeedCronModule;

class DailyFeedCronModule extends BaseDailyFeedCronModule
{
    /**
     * {@inheritdoc}
     */
    public function iterate(): bool
    {
        if ($this->feedExportCreationDataQueue->isEmpty()) {
            $this->logger->addDebug('Queue is empty, no feeds to process.');

            return false;
        }

        if ($this->currentFeedExport === null) {
            $this->currentFeedExport = $this->createCurrentFeedExport();
        }

        $this->currentFeedExport->generateBatch();

        if ($this->currentFeedExport->isFinished()) {
            $feedInfo = $this->currentFeedExport->getFeedInfo();
            $domainConfig = $this->currentFeedExport->getDomainConfig();

            $this->logger->addDebug(sprintf(
                'Feed "%s" generated on domain "%s" into "%s".',
                $feedInfo->getName(),
                $domainConfig->getName(),
                $this->feedFacade->getFeedFilepath($feedInfo, $domainConfig)
            ));

            if ($this->feedExportCreationDataQueue->next() === true) {
                $this->currentFeedExport = $this->createCurrentFeedExport();
                return true;
            } else {
                $this->currentFeedExport = null;
                return false;
            }
        }

        return true;
    }
}
