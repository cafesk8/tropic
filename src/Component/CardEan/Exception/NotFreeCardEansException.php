<?php

declare(strict_types=1);

namespace App\Component\CardEan\Exception;

use Exception;

class NotFreeCardEansException extends Exception implements CardEanException
{
}
