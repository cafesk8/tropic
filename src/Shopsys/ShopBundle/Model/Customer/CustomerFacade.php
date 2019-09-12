<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Country\Country;
use Shopsys\FrameworkBundle\Model\Customer\BillingAddressDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Customer\BillingAddressFactoryInterface;
use Shopsys\FrameworkBundle\Model\Customer\CustomerData;
use Shopsys\FrameworkBundle\Model\Customer\CustomerDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Customer\CustomerFacade as BaseCustomerFacade;
use Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressFactoryInterface;
use Shopsys\FrameworkBundle\Model\Customer\Mail\CustomerMailFacade;
use Shopsys\FrameworkBundle\Model\Customer\UserFactoryInterface;
use Shopsys\FrameworkBundle\Model\Customer\UserRepository;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\ShopBundle\Model\Country\CountryFacade;
use Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEan;
use Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupFacade;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class CustomerFacade extends BaseCustomerFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Customer\UserRepository
     */
    protected $userRepository;

    /**
     * @var \Shopsys\ShopBundle\Model\Country\CountryFacade
     */
    private $countryFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Customer\UserRepository $userRepository
     * @param \Shopsys\FrameworkBundle\Model\Customer\CustomerDataFactoryInterface $customerDataFactory
     * @param \Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface $encoderFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\Mail\CustomerMailFacade $customerMailFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\BillingAddressFactoryInterface $billingAddressFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressFactoryInterface $deliveryAddressFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\BillingAddressDataFactoryInterface $billingAddressDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\UserFactoryInterface $userFactory
     * @param \Shopsys\ShopBundle\Model\Country\CountryFacade $countryFacade
     * @param \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        UserRepository $userRepository,
        CustomerDataFactoryInterface $customerDataFactory,
        EncoderFactoryInterface $encoderFactory,
        CustomerMailFacade $customerMailFacade,
        BillingAddressFactoryInterface $billingAddressFactory,
        DeliveryAddressFactoryInterface $deliveryAddressFactory,
        BillingAddressDataFactoryInterface $billingAddressDataFactory,
        UserFactoryInterface $userFactory,
        CountryFacade $countryFacade,
        PricingGroupFacade $pricingGroupFacade
    ) {
        parent::__construct($em, $userRepository, $customerDataFactory, $encoderFactory, $customerMailFacade, $billingAddressFactory, $deliveryAddressFactory, $billingAddressDataFactory, $userFactory);

        $this->countryFacade = $countryFacade;
        $this->pricingGroupFacade = $pricingGroupFacade;
    }

    /**
     * @param int[] $customerIds
     * @return \Shopsys\ShopBundle\Model\Customer\User[]
     */
    public function getUsersByIds(array $customerIds): array
    {
        return $this->userRepository->getUsersByIds($customerIds);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\User $user
     * @param \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup
     */
    public function changePricingGroup(User $user, PricingGroup $pricingGroup): void
    {
        $user->setPricingGroup($pricingGroup);
        $this->em->flush($user);
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Customer\User[]
     */
    public function getAllUsers(): array
    {
        return $this->userRepository->getAllUsers();
    }

    /**
     * @param int $limit
     * @return \Shopsys\ShopBundle\Model\Order\Order[]
     */
    public function getNotExportedCustomersBatch(int $limit): array
    {
        return $this->userRepository->getNotExportedCustomersBatch($limit);
    }

    /**
     * @param int $userId
     */
    public function markCustomerAsExported(int $userId): void
    {
        /** @var \Shopsys\ShopBundle\Model\Customer\User $user */
        $user = $this->getUserById($userId);
        $user->markAsExported();

        $this->em->flush($user);
    }

    /**
     * @param int $userId
     */
    public function markCustomerAsFailedExported(int $userId): void
    {
        /** @var \Shopsys\ShopBundle\Model\Customer\User $user */
        $user = $this->getUserById($userId);
        $user->markAsFailedExported();

        $this->flush($user);
    }

    /**
     * @param int $limit
     * @return \Shopsys\ShopBundle\Model\Customer\User[]
     */
    public function getCustomersWithoutDeliveryAddress(int $limit): array
    {
        return $this->userRepository->getCustomersWithoutDeliveryAddress($limit);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\User $user
     */
    public function flush(User $user): void
    {
        $this->em->flush($user);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\User $user
     * @param string $transferId
     * @return \Shopsys\ShopBundle\Model\Customer\User
     */
    public function editCustomerTransferId(User $user, string $transferId): User
    {
        $user->setTransferId($transferId);
        $this->em->flush($user);

        return $user;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\User $customer
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEan $transferIdAndEan
     * @param float $discountByCoefficientForEan
     */
    public function updateTransferIdAndEanAndPricingGroup(User $customer, UserTransferIdAndEan $transferIdAndEan, float $discountByCoefficientForEan): void
    {
        $newPricingGroup = $this->pricingGroupFacade->findByDiscount($discountByCoefficientForEan);

        if ($newPricingGroup === null) {
            return;
        }

        $customer->updateTransferEanAndPricingGroup($transferIdAndEan, $newPricingGroup);
        $this->flush($customer);
    }
}
