<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\MassEdit\Exception;

use Exception;

class UnknownMassEditSelectionTypeException extends Exception implements MassEditExceptionInterface
{
    /**
     * @param string $selectionType
     * @param \Exception|null $previous
     */
    public function __construct($selectionType, ?Exception $previous = null)
    {
        parent::__construct(sprintf('Selection type "%s" is not supported', $selectionType), 0, $previous);
    }
}
