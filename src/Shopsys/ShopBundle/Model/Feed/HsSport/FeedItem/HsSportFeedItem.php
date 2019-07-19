<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Model\Feed\HsSport\FeedItem;

use Shopsys\FrameworkBundle\Model\Feed\FeedItemInterface;

class HsSportFeedItem implements FeedItemInterface
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $var;

    /**
     * @var string|null
     */
    protected $code;

    /**
     * @var string|null
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $shortDescription;

    /**
     * @var string|null
     */
    protected $description;

    /**
     * @var string
     */
    protected $price;

    /**
     * @var string
     */
    protected $originalPrice;

    /**
     * @var string
     */
    protected $currencyCode;

    /**
     * @var string[]
     */
    protected $imagesUrls;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Category\Category[]
     */
    protected $categories;

    /**
     * @var \Shopsys\ShopBundle\Model\Feed\HsSport\FeedItem\HsSportFeedVariantItem[]
     */
    protected $variants;

    /**
     * @param int $id
     * @param int|null $var
     * @param string|null $code
     * @param string $name
     * @param string $shortDescription
     * @param string $description
     * @param string $price
     * @param string $originalPrice
     * @param string $currencyCode
     * @param string[] $imagesUrls
     * @param \Shopsys\FrameworkBundle\Model\Category\Category[] $categories
     * @param \Shopsys\ShopBundle\Model\Feed\HsSport\FeedItem\HsSportFeedVariantItem[] $variants
     */
    public function __construct(
        int $id,
        int $var,
        ?string $code,
        ?string $name,
        ?string $shortDescription,
        $description,
        string $price,
        string $originalPrice,
        string $currencyCode,
        array $imagesUrls,
        array $categories,
        array $variants
    ) {
        $this->id = $id;
        $this->var = $var;
        $this->code = $code;
        $this->name = $name;
        $this->shortDescription = $shortDescription;
        $this->description = $description;
        $this->price = $price;
        $this->originalPrice = $originalPrice;
        $this->currencyCode = $currencyCode;
        $this->imagesUrls = $imagesUrls;
        $this->categories = $categories;
        $this->variants = $variants;
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
     * @return string
     */
    public function getVar(): ?int
    {
        return $this->var;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getPrice(): string
    {
        return $this->price;
    }

    /**
     * @return string
     */
    public function getOriginalPrice(): string
    {
        return $this->originalPrice;
    }

    /**
     * @return string
     */
    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    /**
     * @return string[]
     */
    public function getImagesUrls(): array
    {
        return $this->imagesUrls;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Category\Category[]
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Feed\HsSport\FeedItem\HsSportFeedVariantItem[]
     */
    public function getVariants(): array
    {
        return $this->variants;
    }
}
