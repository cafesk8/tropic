<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Category;

use Shopsys\FrameworkBundle\Component\String\TransformString;

class PohodaCategory
{
    public const COL_POHODA_ID = 'pohodaId';
    public const COL_NAME = 'name';
    public const COL_NAME_SK = 'nameSk';
    public const COL_PARENT_ID = 'parentId';
    public const COL_POSITION = 'position';
    public const COL_NOT_LISTABLE = 'not_listable';
    public const COL_LEVEL = 'level';

    /**
     * @var int
     */
    public $pohodaId;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string|null
     */
    public $nameSk;

    /**
     * @var int
     */
    public $parentId;

    /**
     * @var int
     */
    public $position;

    /**
     * @var bool
     */
    public $listable;

    /**
     * @var int
     */
    public $level;

    /**
     * @param array $pohodaCategoryData
     */
    public function __construct(array $pohodaCategoryData)
    {
        $this->pohodaId = (int)$pohodaCategoryData[self::COL_POHODA_ID];
        $this->name = (string)$pohodaCategoryData[self::COL_NAME];
        $this->nameSk = TransformString::emptyToNull((string)$pohodaCategoryData[self::COL_NAME_SK]);
        $this->parentId = (int)$pohodaCategoryData[self::COL_PARENT_ID];
        $this->position = (int)$pohodaCategoryData[self::COL_POSITION];
        $this->listable = !(bool)$pohodaCategoryData[self::COL_NOT_LISTABLE];
        $this->level = (int)$pohodaCategoryData[self::COL_LEVEL];
    }
}
