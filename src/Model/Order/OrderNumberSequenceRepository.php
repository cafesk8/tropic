<?php

declare(strict_types=1);

namespace App\Model\Order;

use Shopsys\FrameworkBundle\Model\Order\OrderNumberSequenceRepository as BaseOrderNumberSequenceRepository;

class OrderNumberSequenceRepository extends BaseOrderNumberSequenceRepository
{
    /**
     * Order number starts with month and day then is completed by a random number so that it always has 10 symbols
     *
     * @return int
     */
    public function getNextNumber()
    {
        $dateString = date('nd');
        $randomNumberLength = 10 - strlen($dateString);

        return intval($dateString . sprintf('%0' . $randomNumberLength . 'd', random_int(0, pow(10, $randomNumberLength) - 1)));
    }
}
