<?php

declare(strict_types=1);

namespace App\Model\Newsletter;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Newsletter\NewsletterSubscriber as BaseNewsletterSubscriber;

/**
 * @ORM\Table(name="newsletter_subscribers")
 * @ORM\Entity
 */
class NewsletterSubscriber extends BaseNewsletterSubscriber
{
    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $exportedToEcomail;

    /**
     * @param string $email
     * @param \DateTimeImmutable $createdAt
     * @param int $domainId
     */
    public function __construct(string $email, DateTimeImmutable $createdAt, int $domainId)
    {
        parent::__construct($email, $createdAt, $domainId);
        $this->exportedToEcomail = false;
    }

    /**
     * @return int
     */
    public function getDomainId(): int
    {
        return $this->domainId;
    }

    /**
     * @return bool
     */
    public function isExportedToEcomail(): bool
    {
        return $this->exportedToEcomail;
    }

    public function setExportedToEcomail(): void
    {
        $this->exportedToEcomail = true;
    }

    public function setNotExportedToEcomail(): void
    {
        $this->exportedToEcomail = false;
    }
}
