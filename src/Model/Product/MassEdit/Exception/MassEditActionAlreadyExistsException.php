<?php

declare(strict_types=1);

namespace App\Model\Product\MassEdit\Exception;

use Exception;

class MassEditActionAlreadyExistsException extends Exception implements MassEditExceptionInterface
{
}
