<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Product;

class PohodaParameter
{
    public const POHODA_PARAMETER_TYPE_TEXT_ID = 1;
    public const POHODA_PARAMETER_TYPE_NUMBER_ID = 5;
    public const POHODA_PARAMETER_TYPE_BOOL_ID = 3;
    public const POHODA_PARAMETER_TYPE_LIST_ID = 8;

    public const POHODA_PARAMETER_COL_TYPE_NUMBER = [
        self::POHODA_PARAMETER_TYPE_BOOL_ID,
        self::POHODA_PARAMETER_TYPE_NUMBER_ID,
    ];

    /**
     * @var string
     */
    public $name;

    /**
     * @var array
     */
    public $values;

    /**
     * @var int
     */
    public $type;

    public int $position;

    /**
     * @param string $name
     * @param array $values
     * @param int $type
     * @param int $position
     */
    public function __construct(string $name, array $values, int $type, int $position)
    {
        $this->name = $name;
        $this->values = $values;
        $this->type = $type;
        $this->position = $position;
    }

    /**
     * @return bool
     */
    public function isTypeBool(): bool
    {
        return $this->type === self::POHODA_PARAMETER_TYPE_BOOL_ID;
    }
}
