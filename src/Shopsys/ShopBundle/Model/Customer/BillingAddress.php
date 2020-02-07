<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Customer\BillingAddress as BaseBillingAddress;

/**
 * @ORM\Table(name="billing_addresses")
 * @ORM\Entity
 * @property \Shopsys\ShopBundle\Model\Country\Country|null $country
 * @method \Shopsys\ShopBundle\Model\Country\Country|null getCountry()
 */
class BillingAddress extends BaseBillingAddress
{
    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    protected $companyNumber;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    protected $companyTaxNumber;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=6, nullable=true)
     */
    protected $postcode;
}
