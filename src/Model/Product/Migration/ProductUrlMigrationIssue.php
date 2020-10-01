<?php

declare(strict_types=1);

namespace App\Model\Product\Migration;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="product_url_migration_issue")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class ProductUrlMigrationIssue
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private string $catnum;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private int $domain;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private string $url;

    /**
     * @ORM\Column(type="text", nullable=false)
     */
    private string $message;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $created;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getCatnum(): string
    {
        return $this->catnum;
    }

    /**
     * @param string $catnum
     */
    public function setCatnum(string $catnum): void
    {
        $this->catnum = $catnum;
    }

    /**
     * @return int
     */
    public function getDomain(): int
    {
        return $this->domain;
    }

    /**
     * @param int $domain
     */
    public function setDomain(int $domain): void
    {
        $this->domain = $domain;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return \DateTime|null
     */
    public function getCreated(): ?\DateTime
    {
        return $this->created;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreated(): void
    {
        $this->created = new \DateTime();
    }
}
