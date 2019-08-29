<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Gtm\Data;

use JsonSerializable;
use Shopsys\ShopBundle\Model\Gtm\Exception\GtmException;

class DataLayerPage implements JsonSerializable
{
    public const TYPE_ARTICLE = 'text';
    public const TYPE_BLOG = 'blog';
    public const TYPE_BLOG_ARTICLE = 'article';
    public const TYPE_BRAND = 'manufacturer';
    public const TYPE_CART = 'cart';
    public const TYPE_CATEGORY = 'category';
    public const TYPE_CATEGORY_PRELIST = 'crossroad';
    public const TYPE_HOME = 'home';
    public const TYPE_ORDER_STEP2 = 'step2';
    public const TYPE_ORDER_STEP3 = 'step3';
    public const TYPE_OTHER = 'other';
    public const TYPE_PRECART = 'inserted';
    public const TYPE_PRODUCT = 'product';
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_PURCHASE_FAIL = 'purchase fail';
    public const TYPE_SEARCH = 'search';
    public const TYPE_STORES = 'stores';
    public const TYPE_ABOUT_US = 'about';

    /**
     * @var string|null
     */
    private $type;

    /**
     * @var string[]|null
     */
    private $category;

    /**
     * @var string[]|null
     */
    private $categoryId;

    /**
     * @var string|null
     */
    private $categoryLevel;

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        if (!in_array($type, [
            self::TYPE_ARTICLE,
            self::TYPE_BLOG,
            self::TYPE_BLOG_ARTICLE,
            self::TYPE_BRAND,
            self::TYPE_CART,
            self::TYPE_CATEGORY,
            self::TYPE_CATEGORY_PRELIST,
            self::TYPE_HOME,
            self::TYPE_ORDER_STEP2,
            self::TYPE_ORDER_STEP3,
            self::TYPE_OTHER,
            self::TYPE_PRECART,
            self::TYPE_PRODUCT,
            self::TYPE_PURCHASE,
            self::TYPE_PURCHASE_FAIL,
            self::TYPE_SEARCH,
            self::TYPE_STORES,
            self::TYPE_ABOUT_US,
        ], true)) {
            throw new GtmException(sprintf('Invalid argument $type "%s"', $type));
        }

        $this->type = $type;
    }

    /**
     * @param string[] $categoryId
     */
    public function setCategoryId(array $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    /**
     * @param string[] $category
     */
    public function setCategory(array $category): void
    {
        $this->category = $category;
    }

    /**
     * @param string $categoryLevel
     */
    public function setCategoryLevel(string $categoryLevel): void
    {
        $this->categoryLevel = $categoryLevel;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return array_filter(get_object_vars($this));
    }
}
