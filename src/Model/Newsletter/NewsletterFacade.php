<?php

declare(strict_types=1);

namespace App\Model\Newsletter;

use Shopsys\FrameworkBundle\Model\Newsletter\NewsletterFacade as BaseNewsletterFacade;

/**
 * @property \App\Model\Newsletter\NewsletterRepository $newsletterRepository
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $em, \App\Model\Newsletter\NewsletterRepository $newsletterRepository, \Shopsys\FrameworkBundle\Model\Newsletter\NewsletterSubscriberFactoryInterface $newsletterSubscriberFactory)
 * @method \App\Model\Newsletter\NewsletterSubscriber|null findNewsletterSubscriberByEmailAndDomainId(string $email, int $domainId)
 * @method \App\Model\Newsletter\NewsletterSubscriber getNewsletterSubscriberById(int $id)
 */
class NewsletterFacade extends BaseNewsletterFacade
{
    /**
     * @return \App\Model\Newsletter\NewsletterSubscriber[]
     */
    public function getNewsletterSubscribersForExportToEcomail(): array
    {
        return $this->newsletterRepository->getNewsletterSubscribersForEcomailExport();
    }

    /**
     * @param \App\Model\Newsletter\NewsletterSubscriber $newsletterSubscriber
     */
    public function markAsExportedToEcomail(NewsletterSubscriber $newsletterSubscriber): void
    {
        $newsletterSubscriber->setExportedToEcomail();
        $this->em->persist($newsletterSubscriber);
        $this->em->flush();
    }
}
