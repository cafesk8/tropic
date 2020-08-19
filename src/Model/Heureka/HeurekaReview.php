<?php

declare(strict_types=1);

namespace App\Model\Heureka;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="heureka_reviews")
 * @ORM\Entity
 */
class HeurekaReview
{
    public const HEUREKA_REVIEW_LIMIT = 2;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=60, nullable=true)
     */
    protected $name;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    protected $ratingId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $addedAt;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     */
    protected $totalRatings;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $pros;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $cons;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $summary;

    /**
     * @param \App\Model\Heureka\HeurekaReviewItem $heurekaReviewItem
     */
    public function __construct(HeurekaReviewItem $heurekaReviewItem)
    {
        $this->ratingId = $heurekaReviewItem->ratingId;
        $this->addedAt = $heurekaReviewItem->addedAt;
        $this->totalRatings = $heurekaReviewItem->totalRatings;
        $this->name = $heurekaReviewItem->name;
        $this->pros = $heurekaReviewItem->pros;
        $this->cons = $heurekaReviewItem->cons;
        $this->summary = $heurekaReviewItem->summary;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getRatingId(): int
    {
        return $this->ratingId;
    }

    /**
     * @return \DateTime
     */
    public function getAddedAt(): DateTime
    {
        return $this->addedAt;
    }

    /**
     * @return float
     */
    public function getTotalRating(): float
    {
        return $this->totalRatings;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getPros(): ?string
    {
        return $this->pros;
    }

    /**
     * @return string|null
     */
    public function getCons(): ?string
    {
        return $this->cons;
    }

    /**
     * @return string|null
     */
    public function getSummary(): ?string
    {
        return $this->summary;
    }
}
