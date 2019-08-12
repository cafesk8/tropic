<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Image\Exception;

use Exception;

class MigrateImageToEntityFailedException extends \Exception
{
    /**
     * @param string $entityClassOrName
     * @param \Exception|null $previous
     */
    public function __construct($entityClassOrName, ?Exception $previous = null)
    {
        parent::__construct('Not found image config for entity "' . $entityClassOrName . '".', 0, $previous);
    }
}
