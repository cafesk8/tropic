<?php

declare(strict_types=1);

namespace App\Model\Image;

use Shopsys\ReadModelBundle\Image\ImageView as BaseImageView;

/**
 * @experimental
 *
 * Class representing images in frontend
 * @see https://docs.shopsys.com/en/latest/model/introduction-to-read-model/
 */
class ImageView extends BaseImageView
{
    protected ?int $entityId;

    /**
     * @param int $id
     * @param string $extension
     * @param string $entityName
     * @param string|null $type
     * @param int|null $entityId
     */
    public function __construct(int $id, string $extension, string $entityName, ?string $type, ?int $entityId)
    {
        parent::__construct($id, $extension, $entityName, $type);

        $this->entityId = $entityId;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * @return string
     */
    public function getEntityName(): string
    {
        return $this->entityName;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @return int|null
     */
    public function getEntityId(): ?int
    {
        return $this->entityId;
    }
}
