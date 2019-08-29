<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Gtm\Data;

use JsonSerializable;

class DataLayerProduct implements JsonSerializable
{
    /**
     * @var string|null
     */
    private $id;

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var string|null
     */
    private $sku;

    /**
     * @var string|null
     */
    private $catNumber;

    /**
     * @var string|null
     */
    private $price;

    /**
     * @var string|null
     */
    private $tax;

    /**
     * @var string|null
     */
    private $priceWithTax;

    /**
     * @var string|null
     */
    private $brand;

    /**
     * @var string|null
     */
    private $category;

    /**
     * @var string|null
     */
    private $variant;

    /**
     * @var string|null
     */
    private $availability;

    /**
     * @var string[]|null
     */
    private $labels;

    /**
     * @var int|null
     */
    private $quantity;

    /**
     * @var string|null
     */
    private $list;

    /**
     * @var int|null
     */
    private $position;

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param string $sku
     */
    public function setSku(string $sku): void
    {
        $this->sku = $sku;
    }

    /**
     * @param string $catNumber
     */
    public function setCatNumber(string $catNumber): void
    {
        $this->catNumber = $catNumber;
    }

    /**
     * @param string $price
     */
    public function setPrice(string $price): void
    {
        $this->price = $price;
    }

    /**
     * @param string $tax
     */
    public function setTax(string $tax): void
    {
        $this->tax = $tax;
    }

    /**
     * @param string $priceWithTax
     */
    public function setPriceWithTax(string $priceWithTax): void
    {
        $this->priceWithTax = $priceWithTax;
    }

    /**
     * @param string $brand
     */
    public function setBrand(string $brand): void
    {
        $this->brand = $brand;
    }

    /**
     * @param string $category
     */
    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    /**
     * @param string $variant
     */
    public function setVariant(string $variant): void
    {
        $this->variant = $variant;
    }

    /**
     * @param string $availability
     */
    public function setAvailability(string $availability): void
    {
        $this->availability = $availability;
    }

    /**
     * @param string[] $labels
     */
    public function setLabels(array $labels): void
    {
        $this->labels = $labels;
    }

    /**
     * @param int $quantity
     */
    public function setQuantity(?int $quantity): void
    {
        $this->quantity = $quantity;
    }

    /**
     * @param string $list
     */
    public function setList(?string $list): void
    {
        $this->list = $list;
    }

    /**
     * @param int $position
     */
    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return array_filter(get_object_vars($this));
    }
}
