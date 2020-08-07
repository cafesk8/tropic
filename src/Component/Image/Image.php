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
     * the attribute is used for proper displaying of "supplier sets", @see \App\Model\Product\Product::$supplierSet
     * https://shopsys.atlassian.net/browse/TF-567
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
}
