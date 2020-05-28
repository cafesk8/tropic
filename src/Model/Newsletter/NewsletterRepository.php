<?php

declare(strict_types=1);

namespace App\Model\Newsletter;

use Shopsys\FrameworkBundle\Model\Newsletter\NewsletterRepository as BaseNewsletterRepository;

/**
 * @method \App\Model\Newsletter\NewsletterSubscriber getNewsletterSubscriberById(int $id)
 * @method \App\Model\Newsletter\NewsletterSubscriber|null findNewsletterSubscribeByEmailAndDomainId(string $email, int $domainId)
 */
class NewsletterRepository extends BaseNewsletterRepository
{
    /**
     * @return \App\Model\Newsletter\NewsletterSubscriber[]
     */
    public function getNewsletterSubscribersForEcomailExport(): array
    {
        return $this->getNewsletterSubscriberRepository()->findBy(['exportedToEcomail' => false]);
    }
}
