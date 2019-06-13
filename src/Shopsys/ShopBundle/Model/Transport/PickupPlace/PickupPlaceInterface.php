<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transport\PickupPlace;

interface PickupPlaceInterface
{
    /**
     * @return int
     */
    public function getId(): int;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getFullAddress(): string;

    /**
     * @return string
     */
    public function getStreet(): string;

    /**
     * @return string
     */
    public function getCountryCode(): string;

    /**
     * @return string
     */
    public function getPostCode(): string;
}
