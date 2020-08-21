<?php

declare(strict_types=1);

namespace App\Model\Feed\Mergado\FeedItem;

use Shopsys\FrameworkBundle\Model\Feed\FeedItemInterface;

class MergadoFeedItem implements FeedItemInterface
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int|null
     */
    private $itemGroupId;

    /**
     * @var string|null
     */
    private $catnum;

    /**
     * @var string|null
     */
    private $ean;

    /**
     * @var string|null
     */
    private $url;

    /**
     * @var string|null
     */
    private $nameExact;

    /**
     * @var string|null
     */
    private $category;

    /**
     * @var string|null
     */
    private $descriptionShort;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var string[]
     */
    private $benefits;

    /**
     * @var string|null
     */
    private $brand;

    /**
     * @var string
     */
    private $priceWithoutVat;

    /**
     * @var string
     */
    private $priceWithVat;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var string
     */
    private $availability;

    /**
     * @var int
     */
    private $deliveryDays;

    /**
     * @var string|null
     */
    private $image;

    /**
     * @var string[]
     */
    private $alternativeImages;

    /**
     * @var string|null
     */
    private $video;

    /**
     * @var string[]
     */
    private $alternativeVideos;

    /**
     * @var string[]
     */
    private $params;

    /**
     * @var \App\Model\Feed\Mergado\FeedItem\MergadoFeedDeliveryItem[]
     */
    private $deliveries;

    /**
     * @var int|null
     */
    private $warranty;

    private string $purchaseVsSellingPriceDifference;

    private ?int $saleExclusionType;

    /**
     * @param int $id
     * @param int|null $itemGroupId
     * @param string|null $catnum
     * @param string|null $ean
     * @param string|null $url
     * @param string|null $nameExact
     * @param string|null $category
     * @param string|null $descriptionShort
     * @param string|null $description
     * @param string[] $benefits
     * @param string|null $brand
     * @param string $price
     * @param string $priceWithVat
     * @param string $currency
     * @param string $availability
     * @param int $deliveryDays
     * @param string|null $image
     * @param string[] $imagesAlternative
     * @param string|null $video
     * @param array $videosAlternative
     * @param string[] $params
     * @param \App\Model\Feed\Mergado\FeedItem\MergadoFeedDeliveryItem[] $deliveries
     * @param int|null $warranty
     * @param string $purchaseVsSellingPriceDifference
     * @param int|null $saleExclusionType
     */
    public function __construct(
        int $id,
        ?int $itemGroupId,
        ?string $catnum,
        ?string $ean,
        ?string $url,
        ?string $nameExact,
        ?string $category,
        ?string $descriptionShort,
        ?string $description,
        array $benefits,
        ?string $brand,
        string $price,
        string $priceWithVat,
        string $currency,
        string $availability,
        int $deliveryDays,
        ?string $image,
        array $imagesAlternative,
        ?string $video,
        array $videosAlternative,
        array $params,
        array $deliveries,
        ?int $warranty,
        string $purchaseVsSellingPriceDifference,
        ?int $saleExclusionType
    ) {
        $this->id = $id;
        $this->itemGroupId = $itemGroupId;
        $this->catnum = $catnum;
        $this->ean = $ean;
        $this->url = $url;
        $this->nameExact = $nameExact;
        $this->category = $category;
        $this->descriptionShort = $descriptionShort;
        $this->description = $description;
        $this->benefits = $benefits;
        $this->brand = $brand;
        $this->priceWithoutVat = $price;
        $this->priceWithVat = $priceWithVat;
        $this->currency = $currency;
        $this->availability = $availability;
        $this->deliveryDays = $deliveryDays;
        $this->image = $image;
        $this->alternativeImages = $imagesAlternative;
        $this->video = $video;
        $this->alternativeVideos = $videosAlternative;
        $this->params = $params;
        $this->deliveries = $deliveries;
        $this->warranty = $warranty;
        $this->purchaseVsSellingPriceDifference = $purchaseVsSellingPriceDifference;
        $this->saleExclusionType = $saleExclusionType;
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
     * @return int|null
     */
    public function getItemGroupId(): ?int
    {
        return $this->itemGroupId;
    }

    /**
     * @return string|null
     */
    public function getCatnum(): ?string
    {
        return $this->catnum;
    }

    /**
     * @return string|null
     */
    public function getEan(): ?string
    {
        return $this->ean;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @return string|null
     */
    public function getNameExact(): ?string
    {
        return $this->nameExact;
    }

    /**
     * @return string|null
     */
    public function getCategory(): ?string
    {
        return $this->category;
    }

    /**
     * @return string|null
     */
    public function getDescriptionShort(): ?string
    {
        return $this->descriptionShort;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return string[]
     */
    public function getBenefits(): array
    {
        return $this->benefits;
    }

    /**
     * @return string|null
     */
    public function getBrand(): ?string
    {
        return $this->brand;
    }

    /**
     * @return string
     */
    public function getPriceWithoutVat(): string
    {
        return $this->priceWithoutVat;
    }

    /**
     * @return string
     */
    public function getPriceWithVat(): string
    {
        return $this->priceWithVat;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @return string
     */
    public function getAvailability(): string
    {
        return $this->availability;
    }

    /**
     * @return int
     */
    public function getDeliveryDays(): int
    {
        return $this->deliveryDays;
    }

    /**
     * @return string|null
     */
    public function getImage(): ?string
    {
        return $this->image;
    }

    /**
     * @return string[]
     */
    public function getAlternativeImages(): array
    {
        return $this->alternativeImages;
    }

    /**
     * @return string|null
     */
    public function getVideo(): ?string
    {
        return $this->video;
    }

    /**
     * @return string[]
     */
    public function getAlternativeVideos(): array
    {
        return $this->alternativeVideos;
    }

    /**
     * @return string[]
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @return \App\Model\Feed\Mergado\FeedItem\MergadoFeedDeliveryItem[]
     */
    public function getDeliveries(): array
    {
        return $this->deliveries;
    }

    /**
     * @return int|null
     */
    public function getWarranty(): ?int
    {
        return $this->warranty;
    }

    /**
     * @return string
     */
    public function getPurchaseVsSellingPriceDifference(): string
    {
        return $this->purchaseVsSellingPriceDifference;
    }

    /**
     * @return int|null
     */
    public function getSaleExclusionType(): ?int
    {
        return $this->saleExclusionType;
    }
}
