<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Product;

class PohodaProduct
{
    public const COL_POHODA_ID = 'pohodaId';
    public const COL_NAME = 'name';
    public const COL_NAME_SK = 'nameSk';
    public const COL_CATNUM = 'catnum';
    public const COL_SHORT_DESCRIPTION = 'shortDescription';
    public const COL_LONG_DESCRIPTION = 'longDescription';

    /**
     * @var int
     */
    public $pohodaId;

    /**
     * @var string
     */
    public $catnum;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $nameSk;

    /**
     * @var string
     */
    public $shortDescription;

    /**
     * @var string
     */
    public $longDescription;

    /**
     * @param array $pohodaProductData
     */
    public function __construct(array $pohodaProductData)
    {
        $this->pohodaId = (int)$pohodaProductData[self::COL_POHODA_ID];
        $this->catnum = (string)$pohodaProductData[self::COL_CATNUM];
        $this->name = (string)$pohodaProductData[self::COL_NAME];
        $this->nameSk = (string)$pohodaProductData[self::COL_NAME_SK];
        $this->shortDescription = (string)$pohodaProductData[self::COL_SHORT_DESCRIPTION];
        $this->longDescription = (string)$pohodaProductData[self::COL_LONG_DESCRIPTION];
    }
}
