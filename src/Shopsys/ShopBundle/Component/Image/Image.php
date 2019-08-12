<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Image;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Component\Image\Image as BaseImage;

/**
 * @ORM\Table(name="images", indexes={@ORM\Index(columns={"entity_name", "entity_id", "type"})})
 * @ORM\Entity
 */
class Image extends BaseImage
{
    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $migrateFileName;

    /**
     * @return string|null
     */
    public function getMigrateFileName(): ?string
    {
        return $this->migrateFileName;
    }

    /**
     * @param string|null $migrateFileName
     */
    public function setMigrateFileName(?string $migrateFileName): void
    {
        $this->migrateFileName = $migrateFileName;
        // workaround: Entity must be changed so that preUpdate and postUpdate are called
        $this->modifiedAt = new DateTime();
    }
}
