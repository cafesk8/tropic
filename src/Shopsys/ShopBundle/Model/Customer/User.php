<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Customer\BillingAddress;
use Shopsys\FrameworkBundle\Model\Customer\DeliveryAddress;
use Shopsys\FrameworkBundle\Model\Customer\User as BaseUser;
use Shopsys\FrameworkBundle\Model\Customer\UserData as BaseUserData;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

/**
 * @ORM\Table(
 *     name="users",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="email_domain", columns={"email", "domain_id"})
 *     },
 *     indexes={
 *         @ORM\Index(columns={"email"})
 *     }
 * )
 * @ORM\Entity
 */
class User extends BaseUser
{
    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $transferId;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true, unique=true)
     */
    private $branchNumber;

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\UserData $userData
     * @param \Shopsys\FrameworkBundle\Model\Customer\BillingAddress $billingAddress
     * @param \Shopsys\FrameworkBundle\Model\Customer\DeliveryAddress|null $deliveryAddress
     * @param \Shopsys\ShopBundle\Model\Customer\User|null $userByEmail
     */
    public function __construct(
        BaseUserData $userData,
        BillingAddress $billingAddress,
        ?DeliveryAddress $deliveryAddress,
        ?BaseUser $userByEmail
    ) {
        parent::__construct($userData, $billingAddress, $deliveryAddress, $userByEmail);

        $this->transferId = $userData->transferId;
        $this->branchNumber = $userData->branchNumber;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\UserData $userData
     * @param \Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface $encoderFactory
     */
    public function edit(BaseUserData $userData, EncoderFactoryInterface $encoderFactory)
    {
        parent::edit($userData, $encoderFactory);
        $this->branchNumber = $userData->branchNumber;
    }

    /**
     * @return string|null
     */
    public function getBranchNumber(): ?string
    {
        return $this->branchNumber;
    }

    /**
     * @return string|null
     */
    public function getTransferId(): ?string
    {
        return $this->transferId;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup
     */
    public function setPricingGroup(PricingGroup $pricingGroup): void
    {
        $this->pricingGroup = $pricingGroup;
    }

    /**
     * @param \Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface $encoderFactory
     * @param string $password
     */
    public function changePasswordByMigration(EncoderFactoryInterface $encoderFactory, $password): void
    {
        $encoder = $encoderFactory->getEncoder($this);

        if ($encoder instanceof BushmanCustomPasswordEncoder) {
            $passwordHash = $encoder->getHashOfMigratedPassword($password, null);
            $this->password = $passwordHash;
            $this->resetPasswordHash = null;
            $this->resetPasswordHashValidThrough = null;
            return;
        }

        parent::changePassword($encoderFactory, $password);
    }
}
