<?php

declare(strict_types=1);

namespace App\Model\Newsletter\Transfer;

use App\Component\Transfer\AbstractTransferCronModule;
use App\Component\Transfer\TransferCronModuleDependency;
use App\Model\Newsletter\NewsletterFacade;

class EcomailExportCronModule extends AbstractTransferCronModule
{
    public const TRANSFER_IDENTIFIER = 'export_ecomail';

    /**
     * @var \App\Model\Newsletter\Transfer\EcomailClient
     */
    private $ecomailClient;

    /**
     * @var \App\Model\Newsletter\NewsletterFacade
     */
    private $newsletterFacade;

    /**
     * @param \App\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \App\Model\Newsletter\Transfer\EcomailClient $ecomailClient
     * @param \App\Model\Newsletter\NewsletterFacade $newsletterFacade
     */
    public function __construct(TransferCronModuleDependency $transferCronModuleDependency, EcomailClient $ecomailClient, NewsletterFacade $newsletterFacade)
    {
        parent::__construct($transferCronModuleDependency);
        $this->ecomailClient = $ecomailClient;
        $this->newsletterFacade = $newsletterFacade;
    }

    /**
     * @return string
     */
    protected function getTransferIdentifier(): string
    {
        return self::TRANSFER_IDENTIFIER;
    }

    /**
     * @return bool
     */
    protected function runTransfer(): bool
    {
        $this->logger->addInfo('Začátek přenosu odběratelů do Ecomail');
        $subscribersCount = 0;

        foreach ($this->newsletterFacade->getNewsletterSubscribersForExportToEcomail() as $newsletterSubscriber) {
            if ($this->ecomailClient->addSubscriber($newsletterSubscriber)) {
                $this->newsletterFacade->markAsExportedToEcomail($newsletterSubscriber);
                $subscribersCount++;
            }
        }

        $this->logger->addInfo('Do Ecomail bylo přeneseno ' . $subscribersCount . ' odběratelů');

        return false;
    }
}
