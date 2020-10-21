<?php

declare(strict_types=1);

namespace App\Model\Newsletter;

use DateTimeImmutable;
use Shopsys\FrameworkBundle\Model\Newsletter\NewsletterFacade as BaseNewsletterFacade;

/**
 * @property \App\Model\Newsletter\NewsletterRepository $newsletterRepository
 * @property \App\Component\EntityExtension\EntityManagerDecorator $em
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
        $this->em->flush($newsletterSubscriber);
    }

    /**
     * @param \App\Model\Newsletter\NewsletterSubscriber $newsletterSubscriber
     */
    public function markAsNotExportedToEcomail(NewsletterSubscriber $newsletterSubscriber): void
    {
        $newsletterSubscriber->setNotExportedToEcomail();
        $this->em->persist($newsletterSubscriber);
        $this->em->flush($newsletterSubscriber);
    }

    /**
     * @param string $email
     * @param int $domainId
     */
    public function addSubscribedEmail($email, $domainId)
    {
        $newsletterSubscriber = $this->newsletterRepository->findNewsletterSubscribeByEmailAndDomainId($email, $domainId);

        if ($newsletterSubscriber === null) {
            $newsletterSubscriber = $this->newsletterSubscriberFactory->create($email, new DateTimeImmutable(), $domainId);
            $this->em->persist($newsletterSubscriber);
            $this->em->flush($newsletterSubscriber);
        } else {
            $this->markAsNotExportedToEcomail($newsletterSubscriber);
        }
    }
}
