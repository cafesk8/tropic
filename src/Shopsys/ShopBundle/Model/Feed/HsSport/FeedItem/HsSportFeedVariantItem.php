<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Model\Feed\HsSport\FeedItem;

use Shopsys\FrameworkBundle\Model\Feed\FeedItemInterface;

class HsSportFeedVariantItem implements FeedItemInterface
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string|null
     */
    protected $ean;

    /**
     * @var string[]
     */
    protected $imagesUrls;

    /**
     * @var string|null
     */
    protected $size;

    /**
     * @var string|null
     */
    protected $color;

    /**
     * @var int
     */
    protected $availability;

    /**
     * @param int $id
     * @param string|null $ean
     * @param string[] $imagesUrls
     * @param string $size|null
     * @param string $color|null
     * @param int $availability
     */
    public function __construct(
        int $id,
        ?string $ean,
        array $imagesUrls,
        ?string $size,
        ?string $color,
        int $availability
    ) {
        $this->id = $id;
        $this->ean = $ean;
        $this->imagesUrls = $imagesUrls;
        $this->size = $size;
        $this->color = $color;
        $this->availability = $availability;
    }

    /**
     * @return int
     */
    public function getSeekId(): int
    {
        return $this->id;
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
    public function getEan(): ?string
    {
        return $this->ean;
    }

    /**
     * @return string[]
     */
    public function getImagesUrls(): array
    {
        return $this->imagesUrls;
    }

    /**
     * @return string|null
     */
    public function getSize(): ?string
    {
        return $this->size;
    }

    /**
     * @return string|null
     */
    public function getColor(): ?string
    {
        return $this->color;
    }

    /**
     * @return int
     */
    public function getAvailability(): int
    {
        return $this->availability;
    }
}
