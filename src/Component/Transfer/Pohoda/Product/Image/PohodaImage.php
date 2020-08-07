<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Product\Image;

class PohodaImage
{
    public const ALIAS_ID = 'id';
    public const ALIAS_DEFAULT = 'default';
    public const ALIAS_PRODUCT_POHODA_ID = 'productPohodaId';
    public const ALIAS_FILE = 'file';
    public const ALIAS_POSITION = 'position';
    public const ALIAS_DESCRIPTION = 'description';

    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $productPohodaId;

    /**
     * @var int
     */
    public $position;

    /**
     * @var string
     */
    public $file;

    /**
     * @var string
     */
    public $extension;

    public ?string $description;

    /**
     * @param array $pohodaImageData
     */
    public function __construct(array $pohodaImageData)
    {
        $this->id = (int)$pohodaImageData[self::ALIAS_ID];
        $this->productPohodaId = (int)$pohodaImageData[self::ALIAS_PRODUCT_POHODA_ID];
        $this->position = $this->getImagePosition($pohodaImageData);
        $this->file = (string)$pohodaImageData[self::ALIAS_FILE];
        $this->extension = $this->getImageExtension();
        $this->description = $pohodaImageData[self::ALIAS_DESCRIPTION];
    }

    /**
     * It may happen that the image is set as "default", however, it's position is not 1,
     * so we need to shift the positions of the non-default images to be sure the default image position is not overwritten
     *
     * @param array $pohodaImageData
     * @return int
     */
    private function getImagePosition(array $pohodaImageData): int
    {
        if ((bool)$pohodaImageData[self::ALIAS_DEFAULT] === true) {
            return 1;
        }

        return (int)$pohodaImageData[self::ALIAS_POSITION] + 1;
    }

    /**
     * @return string
     */
    private function getImageExtension(): string
    {
        return substr($this->file, strpos($this->file, '.') + 1);
    }
}
