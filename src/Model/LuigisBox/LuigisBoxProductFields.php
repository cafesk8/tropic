<?php

declare(strict_types=1);

namespace App\Model\LuigisBox;

class LuigisBoxProductFields extends LuigisBoxObjectFields
{
    public int $availability;

    public string $availability_color;

    public string $availability_filter;

    public string $availability_text;

    public string $brand;

    public ?string $code;

    public ?string $description;

    public ?string $description_short;

    /**
     * @var string[]
     */
    public array $flag_colors;

    /**
     * @var string[]
     */
    public array $flags;

    public ?string $gift;

    public int $id;

    public ?string $image_link;

    public int $maximum_quantity;

    public int $minimum_quantity;

    public string $price;

    public string $price_amount;

    public string $price_registered;

    public string $price_registered_amount;

    public ?string $price_standard;

    public ?string $price_standard_amount;

    public int $quantity_multiplier;

    public array $set_items;

    public int $variants_count;
}
