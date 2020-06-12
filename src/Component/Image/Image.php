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
}
