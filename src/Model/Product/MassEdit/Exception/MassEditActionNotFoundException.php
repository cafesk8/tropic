<?php

declare(strict_types=1);

namespace App\Model\Product\MassEdit\Exception;

use Exception;

class MassEditActionNotFoundException extends Exception implements MassEditExceptionInterface
{
}
