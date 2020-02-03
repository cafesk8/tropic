<?php

declare(strict_types=1);

namespace App\Model\Product\Brand\Exception;

use Exception;
use Shopsys\FrameworkBundle\Model\Product\Brand\Exception\BrandException;

class BrandDeletionForbiddenException extends Exception implements BrandException
{
}
