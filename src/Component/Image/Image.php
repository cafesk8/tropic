<?php

declare(strict_types=1);

namespace App\Component\Image;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Component\Image\Image as BaseImage;

/**
 * @ORM\Table(name="images", indexes={@ORM\Index(columns={"entity_name", "entity_id", "type"})})
 * @ORM\Entity
 */
class Image extends BaseImage
{
    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $pohodaId;

    /**
     * The attribute is used for proper displaying of "supplier sets", @see \App\Model\Product\Product::$supplierSet
     * @see https://shopsys.atlassian.net/browse/TF-567
     *
     * Also, we use it for detection whether the main variant's image, the variant's image, or the no-image should be displayed
     * @see https://shopsys.atlassian.net/browse/TF-746
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $description = null;

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @deprecated Generating image names for products has changed
     * @return string
     */
    public function getFilename(): string
    {
        return $this->id . '.' . $this->extension;
    }

    /**
     * @param string $extension
     */
    public function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }

    /**
     * @param int|null $pohodaId
     */
    public function setPohodaId(?int $pohodaId): void
    {
        $this->pohodaId = $pohodaId;
    }

    /**
     * @param string|null $description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
}
