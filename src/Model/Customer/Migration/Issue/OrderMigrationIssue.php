<?php

declare(strict_types=1);

namespace App\Model\Customer\Migration\Issue;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="order_migration_issue")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class OrderMigrationIssue
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $id;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private int $orderLegacyId;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private string $orderLegacyNumber;

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
     * @return int
     */
    public function getOrderLegacyId(): int
    {
        return $this->orderLegacyId;
    }

    /**
     * @param int $orderLegacyId
     */
    public function setOrderLegacyId(int $orderLegacyId): void
    {
        $this->orderLegacyId = $orderLegacyId;
    }

    /**
     * @return string
     */
    public function getOrderLegacyNumber(): string
    {
        return $this->orderLegacyNumber;
    }

    /**
     * @param string $orderLegacyNumber
     */
    public function setOrderLegacyNumber(string $orderLegacyNumber): void
    {
        $this->orderLegacyNumber = $orderLegacyNumber;
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
