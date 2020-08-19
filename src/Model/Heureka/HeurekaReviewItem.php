<?php

declare(strict_types=1);

namespace App\Model\Heureka;

use DateTime;

class HeurekaReviewItem
{
    /**
     * @var int
     */
    public $ratingId;

    /**
     * @var \DateTime
     */
    public $addedAt;

    /**
     * @var string
     */
    public $name;

    /**
     * @var float
     */
    public $totalRatings;

    /**
     * @var string|null
     */
    public $pros;

    /**
     * @var string|null
     */
    public $cons;

    /**
     * @var string|null
     */
    public $summary;

    /**
     * @param int $ratingId
     * @param \DateTime $addedAt
     * @param float $totalRatings
     * @param string $name
     * @param string|null $pros
     * @param string|null $cons
     * @param string|null $summary
     */
    public function __construct(
        int $ratingId,
        DateTime $addedAt,
        float $totalRatings,
        string $name,
        ?string $pros,
        ?string $cons,
        ?string $summary
    ) {
        $this->ratingId = $ratingId;
        $this->addedAt = $addedAt;
        $this->totalRatings = $totalRatings;
        $this->name = $name;
        $this->pros = $pros;
        $this->cons = $cons;
        $this->summary = $summary;
    }
}
