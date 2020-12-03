<?php

declare(strict_types=1);

namespace App\Model\Product\Search;

class BrandSearchResult
{
    /**
     * @var \App\Model\Product\Brand\Brand[]
     */
    private array $results;

    private int $totalCount;

    /**
     * @param \App\Model\Product\Brand\Brand[] $results
     * @param int $totalCount
     */
    public function __construct(array $results, int $totalCount)
    {
        $this->results = $results;
        $this->totalCount = $totalCount;
    }

    /**
     * @return \App\Model\Product\Brand\Brand[]
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * @return int
     */
    public function getTotalCount(): int
    {
        return $this->totalCount;
    }
}