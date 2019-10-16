<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transfer;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="transfers")
 *
 * @ORM\Entity
 */
class Transfer
{
    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=100, nullable=false, unique=true)
     */
    private $identifier;

    /**
     * @var string
     * @ORM\Column(type="string", length=100, nullable=false)
     */
    private $name;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastStartAt;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastFinishAt;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $inProgress;

    /**
     * @var string
     * @ORM\Column(type="string", length=100, nullable=false)
     */
    private $frequency;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $enabled;

    public function setAsInProgress()
    {
        $this->inProgress = true;
    }

    /**
     * @param \DateTime $lastStartAt
     */
    public function setAsFinished(DateTime $lastStartAt)
    {
        $this->inProgress = false;
        $this->lastStartAt = $lastStartAt;
        $this->lastFinishAt = new DateTime();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastStartAt(): ?DateTime
    {
        return $this->lastStartAt;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastFinishAt(): ?DateTime
    {
        return $this->lastFinishAt;
    }

    /**
     * @return bool
     */
    public function isInProgress(): bool
    {
        return $this->inProgress;
    }

    /**
     * @return string
     */
    public function getFrequency(): string
    {
        return $this->frequency;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param \DateTime|null $lastStartAt
     */
    public function setLastStartAt(?\DateTime $lastStartAt): void
    {
        $this->lastStartAt = $lastStartAt;
    }
}
