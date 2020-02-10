<?php

declare(strict_types=1);

namespace App\Model\Transport\Exception;

use App\Model\Transfer\Exception\TransferException;
use Exception;

class InvalidPersonalTakeTypeException extends Exception implements TransferException
{
}
