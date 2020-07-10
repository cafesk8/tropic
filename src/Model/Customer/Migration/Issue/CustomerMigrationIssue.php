<?php

declare(strict_types=1);

namespace App\Model\Customer\Migration\Issue;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="customer_migration_issue")
 * @ORM\Entity
 */
class CustomerMigrationIssue
{
    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    protected $customerLegacyId;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    protected $customerEmail;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=false)
     */
    protected $message;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $created;

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
    public function setId(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getCustomerLegacyId(): string
    {
        return $this->customerLegacyId;
    }

    /**
     * @param string $customerLegacyId
     */
    public function setCustomerLegacyId(string $customerLegacyId)
    {
        $this->customerLegacyId = $customerLegacyId;
    }

    /**
     * @return string
     */
    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    /**
     * @param string $customerEmail
     */
    public function setCustomerEmail(string $customerEmail)
    {
        $this->customerEmail = $customerEmail;
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
    public function setMessage(string $message)
    {
        $this->message = $message;
    }

    /**
     * @return \DateTime|null
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTime|null $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }
}
