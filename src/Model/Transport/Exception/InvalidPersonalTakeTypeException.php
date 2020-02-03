<?php

declare(strict_types=1);

namespace App\Model\Transport\Exception;

use Exception;
use App\Model\Transfer\Exception\TransferException;

class InvalidPersonalTakeTypeException extends Exception implements TransferException
{
}
