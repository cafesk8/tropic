<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transport\PickupPlace\Exception;

use Exception;
use Shopsys\FrameworkBundle\Model\Transport\Exception\TransportException;

class PickupPlaceNotFoundException extends Exception implements TransportException
{
}
