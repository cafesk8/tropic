<?php

declare(strict_types=1);

namespace App\Model\Product\View;

use Shopsys\ReadModelBundle\Product\Action\ProductActionView as BaseProductActionView;

class ProductActionView extends BaseProductActionView
{
    /**
     * @var int
     */
    private $minimumAmount;

    /**
     * @var int
     */
    private $amountMultiplier;

    /**
     * @param int $id
     * @param bool $sellingDenied
     * @param bool $mainVariant
     * @param string $detailUrl
     * @param int $minimumAmount
     * @param int $amountMultiplier
     */
    public function __construct(int $id, bool $sellingDenied, bool $mainVariant, string $detailUrl, int $minimumAmount, int $amountMultiplier)
    {
        parent::__construct($id, $sellingDenied, $mainVariant, $detailUrl);
        $this->minimumAmount = $minimumAmount;
        $this->amountMultiplier = $amountMultiplier;
    }

    /**
     * @return int
     */
    public function getAmountMultiplier(): int
    {
        return $this->amountMultiplier;
    }

    /**
     * @return int
     */
    public function getMinimumAmount(): int
    {
        return $this->minimumAmount;
    }
}
