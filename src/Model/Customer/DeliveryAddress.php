<?php

declare(strict_types=1);

namespace App\Model\Customer;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Customer\DeliveryAddress as BaseDeliveryAddress;

/**
 * @ORM\Table(name="delivery_addresses")
 * @ORM\Entity
 * @property \App\Model\Country\Country|null $country
 * @method \App\Model\Country\Country|null getCountry()
 */
class DeliveryAddress extends BaseDeliveryAddress
{
    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=60, nullable=true)
     */
    protected $firstName;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    protected $lastName;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=6, nullable=true)
     */
    protected $postcode;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    protected $telephone;
}
