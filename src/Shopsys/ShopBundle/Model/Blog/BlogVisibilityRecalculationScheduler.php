<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Blog;

class BlogVisibilityRecalculationScheduler
{
    /**
     * @var bool
     */
    private $recalculate = false;

    public function scheduleRecalculation(): void
    {
        $this->recalculate = true;
    }

    /**
     * @return bool
     */
    public function isRecalculationScheduled(): bool
    {
        return $this->recalculate;
    }
}
