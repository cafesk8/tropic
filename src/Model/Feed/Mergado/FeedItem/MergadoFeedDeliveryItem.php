<?php

declare(strict_types=1);

namespace App\Model\Feed\Mergado\FeedItem;

use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Feed\FeedItemInterface;

class MergadoFeedDeliveryItem implements FeedItemInterface
{
    /**
     * @var int
     */
    protected $transportId;

    /**
     * @var string
     */
    protected $mergadoId;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money
     */
    protected $price;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    protected $priceCod;

    /**
     * @param int $transportId
     * @param string $mergadoId
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $price
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $priceCod
     */
    public function __construct(
        int $transportId,
        string $mergadoId,
        Money $price,
        ?Money $priceCod
    ) {
        $this->transportId = $transportId;
        $this->mergadoId = $mergadoId;
        $this->price = $price;
        $this->priceCod = $priceCod;
    }

    /**
     * @inheritDoc
     */
    public function getSeekId(): int
    {
        return $this->transportId;
    }

    /**
     * @return int
     */
    public function getTransportId(): int
    {
        return $this->transportId;
    }

    /**
     * @return string
     */
    public function getMergadoId(): string
    {
        return $this->mergadoId;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money
     */
    public function getPrice(): Money
    {
        return $this->price;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    public function getPriceCod(): ?Money
    {
        return $this->priceCod;
    }
}
