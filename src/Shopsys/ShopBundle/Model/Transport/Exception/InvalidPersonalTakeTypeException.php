<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transport\Exception;

use Exception;
use Shopsys\ShopBundle\Model\Transfer\Exception\TransferException;

class InvalidPersonalTakeTypeException extends Exception implements TransferException
{
}
