<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Customer\BillingAddressDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Customer\BillingAddressFactoryInterface;
use Shopsys\FrameworkBundle\Model\Customer\CustomerData;
use Shopsys\FrameworkBundle\Model\Customer\CustomerDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Customer\CustomerFacade as BaseCustomerFacade;
use Shopsys\FrameworkBundle\Model\Customer\CustomerPasswordFacade;
use Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressFactoryInterface;
use Shopsys\FrameworkBundle\Model\Customer\Mail\CustomerMailFacade;
use Shopsys\FrameworkBundle\Model\Customer\UserFactoryInterface;
use Shopsys\FrameworkBundle\Model\Customer\UserRepository;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\ShopBundle\Model\Customer\TransferIds\UserTransferId;
use Shopsys\ShopBundle\Model\Customer\TransferIds\UserTransferIdDataFactory;
use Shopsys\ShopBundle\Model\Customer\TransferIds\UserTransferIdFacade;
use Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupFacade;

class CustomerFacade extends BaseCustomerFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Customer\UserRepository
     */
    protected $userRepository;

    /**
     * @var \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\TransferIds\UserTransferIdFacade
     */
    private $userTransferIdFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\TransferIds\UserTransferIdDataFactory
     */
    private $userTransferIdDataFactory;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Customer\UserRepository $userRepository
     * @param \Shopsys\FrameworkBundle\Model\Customer\CustomerDataFactoryInterface $customerDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\Mail\CustomerMailFacade $customerMailFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\BillingAddressFactoryInterface $billingAddressFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressFactoryInterface $deliveryAddressFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\BillingAddressDataFactoryInterface $billingAddressDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\UserFactoryInterface $userFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\CustomerPasswordFacade $customerPasswordFacade
     * @param \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIds\UserTransferIdFacade $userTransferIdFacade
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIds\UserTransferIdDataFactory $userTransferIdDataFactory
     */
    public function __construct(
        EntityManagerInterface $em,
        UserRepository $userRepository,
        CustomerDataFactoryInterface $customerDataFactory,
        CustomerMailFacade $customerMailFacade,
        BillingAddressFactoryInterface $billingAddressFactory,
        DeliveryAddressFactoryInterface $deliveryAddressFactory,
        BillingAddressDataFactoryInterface $billingAddressDataFactory,
        UserFactoryInterface $userFactory,
        CustomerPasswordFacade $customerPasswordFacade,
        PricingGroupFacade $pricingGroupFacade,
        UserTransferIdFacade $userTransferIdFacade,
        UserTransferIdDataFactory $userTransferIdDataFactory
    ) {
        parent::__construct($em, $userRepository, $customerDataFactory, $customerMailFacade, $billingAddressFactory, $deliveryAddressFactory, $billingAddressDataFactory, $userFactory, $customerPasswordFacade);

        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->userTransferIdFacade = $userTransferIdFacade;
        $this->userTransferIdDataFactory = $userTransferIdDataFactory;
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

        if (!$this->userTransferIdFacade->isTransferIdExists($user, $transferId)) {
            $userTransferIdData = $this->userTransferIdDataFactory->createFromCustomerTransferId($user, $transferId);
            $this->userTransferIdFacade->create($userTransferIdData);
        }

        return $user;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIds\UserTransferId $transferId
     * @param float $discountByCoefficientForEan
     */
    public function updateTransferIdAndPricingGroup(UserTransferId $transferId, float $discountByCoefficientForEan): void
    {
        $newPricingGroup = $this->pricingGroupFacade->findByDiscount($discountByCoefficientForEan, $transferId->getCustomer()->getDomainId());

        if ($newPricingGroup === null) {
            return;
        }

        $customerId = $transferId->getCustomer()->getId();
        $customer = $this->getUserById($customerId);
        $customer->updateTransferIdAndPricingGroup($transferId, $newPricingGroup);
        $this->flush($customer);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup $currentPricingGroup
     * @param float|null $coefficient
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIds\UserTransferId $userTransferId
     */
    public function updatePricingGroupByIsResponse(PricingGroup $currentPricingGroup, ?float $coefficient, UserTransferId $userTransferId): void
    {
        if ($coefficient !== null && ($currentPricingGroup->getDiscount() === null || $coefficient <= $currentPricingGroup->getDiscount())) {
            $this->updateTransferIdAndPricingGroup(
                $userTransferId,
                $coefficient
            );
        }

        $this->changeCustomerPricingGroupUpdatedAt($userTransferId->getCustomer());
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Customer\User[]
     */
    public function getForPricingGroupUpdate(): array
    {
        return $this->userRepository->getForPricingGroupUpdate();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\User $user
     */
    public function changeCustomerPricingGroupUpdatedAt(User $user): void
    {
        $user = $this->userRepository->getUserById($user->getId());
        $customerData = $this->customerDataFactory->createFromUser($user);

        /** @var \Shopsys\ShopBundle\Model\Customer\UserData $userData */
        $userData = $customerData->userData;
        $userData->pricingGroupUpdatedAt = new DateTime();

        $customerData->userData = $userData;

        $this->editByCustomer($user->getId(), $customerData);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\CustomerData $customerData
     * @return \Shopsys\ShopBundle\Model\Customer\User
     */
    public function registerCustomer(CustomerData $customerData): User
    {
        /** @var \Shopsys\ShopBundle\Model\Customer\UserData $userData */
        $userData = $customerData->userData;

        $billingAddress = $this->billingAddressFactory->create($customerData->billingAddressData);
        $deliveryAddress = null;
        $deliveryAddressData = $customerData->deliveryAddressData;
        if ($userData->memberOfLoyaltyProgram || $deliveryAddressData->addressFilled === true) {
            $deliveryAddressData->addressFilled = true;
            $deliveryAddress = $this->deliveryAddressFactory->create($customerData->deliveryAddressData);
            $this->em->persist($deliveryAddress);
        }

        /** @var \Shopsys\ShopBundle\Model\Customer\User $user */
        $user = $this->userFactory->create(
            $customerData->userData,
            $billingAddress,
            $deliveryAddress
        );

        $this->em->persist($billingAddress);
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
