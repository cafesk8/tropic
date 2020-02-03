<?php

declare(strict_types=1);

namespace App\Model\Customer;

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
use Shopsys\FrameworkBundle\Model\Customer\User;
use Shopsys\FrameworkBundle\Model\Customer\UserFactoryInterface;
use Shopsys\FrameworkBundle\Model\Customer\UserRepository;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use App\Model\Customer\TransferIds\UserTransferId;
use App\Model\Customer\TransferIds\UserTransferIdDataFactory;
use App\Model\Customer\TransferIds\UserTransferIdFacade;
use App\Model\Pricing\Group\PricingGroupFacade;

/**
 * @property \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
 * @property \App\Model\Customer\CustomerDataFactory $customerDataFactory
 * @method \App\Model\Customer\User getUserById(int $userId)
 * @method \App\Model\Customer\User|null findUserByEmailAndDomain(string $email, int $domainId)
 * @method \App\Model\Customer\User register(\App\Model\Customer\UserData $userData)
 * @method \App\Model\Customer\User create(\Shopsys\FrameworkBundle\Model\Customer\CustomerData $customerData)
 * @method \App\Model\Customer\User edit(int $userId, \Shopsys\FrameworkBundle\Model\Customer\CustomerData $customerData)
 * @method editDeliveryAddress(\App\Model\Customer\User $user, \Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressData $deliveryAddressData)
 * @method \App\Model\Customer\User editByAdmin(int $userId, \Shopsys\FrameworkBundle\Model\Customer\CustomerData $customerData)
 * @method \App\Model\Customer\User editByCustomer(int $userId, \Shopsys\FrameworkBundle\Model\Customer\CustomerData $customerData)
 * @method amendCustomerDataFromOrder(\App\Model\Customer\User $user, \App\Model\Order\Order $order)
 * @method setEmail(string $email, \App\Model\Customer\User $user)
 */
class CustomerFacade extends BaseCustomerFacade
{
    /**
     * @var \App\Model\Customer\UserRepository
     */
    protected $userRepository;

    /**
     * @var \App\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @var \App\Model\Customer\TransferIds\UserTransferIdFacade
     */
    private $userTransferIdAndEanFacade;

    /**
     * @var \App\Model\Customer\TransferIds\UserTransferIdDataFactory
     */
    private $userTransferIdAndEanDataFactory;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Customer\UserRepository $userRepository
     * @param \App\Model\Customer\CustomerDataFactory $customerDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\Mail\CustomerMailFacade $customerMailFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\BillingAddressFactoryInterface $billingAddressFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressFactoryInterface $deliveryAddressFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\BillingAddressDataFactoryInterface $billingAddressDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\UserFactoryInterface $userFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\CustomerPasswordFacade $customerPasswordFacade
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \App\Model\Customer\TransferIds\UserTransferIdFacade $userTransferIdAndEanFacade
     * @param \App\Model\Customer\TransferIds\UserTransferIdDataFactory $userTransferIdAndEanDataFactory
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
        UserTransferIdFacade $userTransferIdAndEanFacade,
        UserTransferIdDataFactory $userTransferIdAndEanDataFactory
    ) {
        parent::__construct($em, $userRepository, $customerDataFactory, $customerMailFacade, $billingAddressFactory, $deliveryAddressFactory, $billingAddressDataFactory, $userFactory, $customerPasswordFacade);

        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->userTransferIdAndEanFacade = $userTransferIdAndEanFacade;
        $this->userTransferIdAndEanDataFactory = $userTransferIdAndEanDataFactory;
    }

    /**
     * @param int[] $customerIds
     * @return \App\Model\Customer\User[]
     */
    public function getUsersByIds(array $customerIds): array
    {
        return $this->userRepository->getUsersByIds($customerIds);
    }

    /**
     * @param \App\Model\Customer\User $user
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     */
    public function changePricingGroup(User $user, PricingGroup $pricingGroup): void
    {
        $user->setPricingGroup($pricingGroup);
        $this->em->flush($user);
    }

    /**
     * @return \App\Model\Customer\User[]
     */
    public function getAllUsers(): array
    {
        return $this->userRepository->getAllUsers();
    }

    /**
     * @param int $limit
     * @return \App\Model\Customer\User[]
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
        /** @var \App\Model\Customer\User $user */
        $user = $this->getUserById($userId);
        $user->markAsExported();

        $this->em->flush($user);
    }

    /**
     * @param int $userId
     */
    public function markCustomerAsFailedExported(int $userId): void
    {
        /** @var \App\Model\Customer\User $user */
        $user = $this->getUserById($userId);
        $user->markAsFailedExported();

        $this->flush($user);
    }

    /**
     * @param int $limit
     * @return \App\Model\Customer\User[]
     */
    public function getCustomersWithoutDeliveryAddress(int $limit): array
    {
        return $this->userRepository->getCustomersWithoutDeliveryAddress($limit);
    }

    /**
     * @param \App\Model\Customer\User $user
     */
    public function flush(User $user): void
    {
        $this->em->flush($user);
    }

    /**
     * @param \App\Model\Customer\User $user
     * @param string $transferId
     * @return \App\Model\Customer\User
     */
    public function editCustomerTransferId(User $user, string $transferId): User
    {
        $user->setTransferId($transferId);
        $this->em->flush($user);

        $ean = $user->getEan();
        if ($ean !== null && !$this->userTransferIdAndEanFacade->isTransferIdExists($user, $transferId, $user->getEan())) {
            $userTransferIdAndEanData = $this->userTransferIdAndEanDataFactory->createFromCustomerTransferId($user, $transferId, $ean);
            $this->userTransferIdAndEanFacade->create($userTransferIdAndEanData);
        }

        return $user;
    }

    /**
     * @param \App\Model\Customer\TransferIds\UserTransferId $transferIdAndEan
     * @param float $discountByCoefficientForEan
     */
    public function updateTransferIdAndEanAndPricingGroup(UserTransferId $transferIdAndEan, float $discountByCoefficientForEan): void
    {
        $newPricingGroup = $this->pricingGroupFacade->findByDiscount($discountByCoefficientForEan, $transferIdAndEan->getCustomer()->getDomainId());

        if ($newPricingGroup === null) {
            return;
        }

        $customerId = $transferIdAndEan->getCustomer()->getId();
        $customer = $this->getUserById($customerId);
        $customer->updateTransferEanAndPricingGroup($transferIdAndEan, $newPricingGroup);
        $this->flush($customer);
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $currentPricingGroup
     * @param float|null $coefficient
     * @param \App\Model\Customer\TransferIds\UserTransferId $userTransferIdAndEan
     */
    public function updatePricingGroupByIsResponse(PricingGroup $currentPricingGroup, ?float $coefficient, UserTransferId $userTransferIdAndEan): void
    {
        if ($coefficient !== null && ($currentPricingGroup->getDiscount() === null || $coefficient <= $currentPricingGroup->getDiscount())) {
            $this->updateTransferIdAndEanAndPricingGroup(
                $userTransferIdAndEan,
                $coefficient
            );
        }

        $this->changeCustomerPricingGroupUpdatedAt($userTransferIdAndEan->getCustomer());
    }

    /**
     * @return \App\Model\Customer\User[]
     */
    public function getForPricingGroupUpdate(): array
    {
        return $this->userRepository->getForPricingGroupUpdate();
    }

    /**
     * @param \App\Model\Customer\User $user
     */
    public function changeCustomerPricingGroupUpdatedAt(User $user): void
    {
        $user = $this->userRepository->getUserById($user->getId());
        $customerData = $this->customerDataFactory->createFromUser($user);

        /** @var \App\Model\Customer\UserData $userData */
        $userData = $customerData->userData;
        $userData->pricingGroupUpdatedAt = new DateTime();

        $customerData->userData = $userData;

        $this->editByCustomer($user->getId(), $customerData);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\CustomerData $customerData
     * @return \App\Model\Customer\User
     */
    public function registerCustomer(CustomerData $customerData): User
    {
        /** @var \App\Model\Customer\UserData $userData */
        $userData = $customerData->userData;

        $billingAddress = $this->billingAddressFactory->create($customerData->billingAddressData);
        $deliveryAddress = null;
        $deliveryAddressData = $customerData->deliveryAddressData;
        if ($userData->memberOfLoyaltyProgram || $deliveryAddressData->addressFilled === true) {
            $deliveryAddressData->addressFilled = true;
            $deliveryAddress = $this->deliveryAddressFactory->create($customerData->deliveryAddressData);
            $this->em->persist($deliveryAddress);
        }

        /** @var \App\Model\Customer\User $user */
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
