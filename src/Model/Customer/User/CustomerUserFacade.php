<?php

declare(strict_types=1);

namespace App\Model\Customer\User;

use App\Model\Customer\TransferIds\UserTransferId;
use App\Model\Customer\TransferIds\UserTransferIdDataFactory;
use App\Model\Customer\TransferIds\UserTransferIdFacade;
use App\Model\Pricing\Group\PricingGroupFacade;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Customer\BillingAddressDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Customer\BillingAddressFactoryInterface;
use Shopsys\FrameworkBundle\Model\Customer\CustomerFacade;
use Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressFactoryInterface;
use Shopsys\FrameworkBundle\Model\Customer\Mail\CustomerMailFacade;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserFacade as BaseCustomerUserFacade;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserFactoryInterface;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserPasswordFacade;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserRepository;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;

/**
 * @property \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
 * @property \App\Model\Customer\User\CustomerUserUpdateDataFactory $customerUserUpdateDataFactory
 * @method \App\Model\Customer\User\CustomerUser|null findCustomerUserByEmailAndDomain(string $email, int $domainId)
 * @method \App\Model\Customer\User\CustomerUser register(\App\Model\Customer\User\CustomerUserData $customerUserData)
 * @method \App\Model\Customer\User\CustomerUser create(\Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData $customerUserUpdateData)
 * @method editDeliveryAddress(\App\Model\Customer\User\CustomerUser $customerUser, \Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressData $deliveryAddressData)
 * @method amendCustomerDataFromOrder(\App\Model\Customer\User\CustomerUser $customerUser, \App\Model\Order\Order $order)
 * @method setEmail(string $email, \App\Model\Customer\User\CustomerUser $customerUser)
 * @method \App\Model\Customer\User\CustomerUser getCustomerUserById(int $customerUserId)
 * @method \App\Model\Customer\User\CustomerUser createCustomerUser(\Shopsys\FrameworkBundle\Model\Customer\Customer $customer, \App\Model\Customer\User\CustomerUserData $customerUserData, \Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressData|null $deliveryAddressData)
 * @method \App\Model\Customer\User\CustomerUser edit(int $customerUserId, \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData $customerUserUpdateData)
 * @method \App\Model\Customer\User\CustomerUser editByAdmin(int $customerUserId, \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData $customerUserUpdateData)
 * @method \App\Model\Customer\User\CustomerUser editByCustomerUser(int $customerUserId, \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData $customerUserUpdateData)
 * @method amendCustomerUserDataFromOrder(\App\Model\Customer\User\CustomerUser $customerUser, \App\Model\Order\Order $order)
 */
class CustomerUserFacade extends BaseCustomerUserFacade
{
    /**
     * @var \App\Model\Customer\User\CustomerUserRepository
     */
    protected $customerUserRepository;

    /**
     * @var \App\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @var \App\Model\Customer\TransferIds\UserTransferIdFacade
     */
    private $userTransferIdFacade;

    /**
     * @var \App\Model\Customer\TransferIds\UserTransferIdDataFactory
     */
    private $userTransferIdDataFactory;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Customer\User\CustomerUserRepository $customerUserRepository
     * @param \App\Model\Customer\User\CustomerUserUpdateDataFactory $customerUserUpdateDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\Mail\CustomerMailFacade $customerMailFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\BillingAddressFactoryInterface $billingAddressFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressFactoryInterface $deliveryAddressFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\BillingAddressDataFactoryInterface $billingAddressDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserFactoryInterface $customerUserFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserPasswordFacade $customerUserPasswordFacade
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \App\Model\Customer\TransferIds\UserTransferIdFacade $userTransferIdFacade
     * @param \App\Model\Customer\TransferIds\UserTransferIdDataFactory $userTransferIdDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\CustomerFacade $customerFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        CustomerUserRepository $customerUserRepository,
        CustomerUserUpdateDataFactoryInterface $customerUserUpdateDataFactory,
        CustomerMailFacade $customerMailFacade,
        BillingAddressFactoryInterface $billingAddressFactory,
        DeliveryAddressFactoryInterface $deliveryAddressFactory,
        BillingAddressDataFactoryInterface $billingAddressDataFactory,
        CustomerUserFactoryInterface $customerUserFactory,
        CustomerUserPasswordFacade $customerUserPasswordFacade,
        PricingGroupFacade $pricingGroupFacade,
        UserTransferIdFacade $userTransferIdFacade,
        UserTransferIdDataFactory $userTransferIdDataFactory,
        CustomerFacade $customerFacade
    ) {
        parent::__construct($em, $customerUserRepository, $customerUserUpdateDataFactory, $customerMailFacade, $billingAddressFactory, $deliveryAddressFactory, $billingAddressDataFactory, $customerUserFactory, $customerUserPasswordFacade, $customerFacade);

        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->userTransferIdFacade = $userTransferIdFacade;
        $this->userTransferIdDataFactory = $userTransferIdDataFactory;
    }

    /**
     * @param int[] $customerIds
     * @return \App\Model\Customer\User\CustomerUser[]
     */
    public function getUsersByIds(array $customerIds): array
    {
        return $this->customerUserRepository->getUsersByIds($customerIds);
    }

    /**
     * @param \App\Model\Customer\User\CustomerUser $customerUser
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     */
    public function changePricingGroup(CustomerUser $customerUser, PricingGroup $pricingGroup): void
    {
        $customerUser->setPricingGroup($pricingGroup);
        $this->em->flush($customerUser);
    }

    /**
     * @return \App\Model\Customer\User\CustomerUser[]
     */
    public function getAllUsers(): array
    {
        return $this->customerUserRepository->getAllUsers();
    }

    /**
     * @param int $limit
     * @return \App\Model\Customer\User\CustomerUser[]
     */
    public function getNotExportedCustomersBatch(int $limit): array
    {
        return $this->customerUserRepository->getNotExportedCustomersBatch($limit);
    }

    /**
     * @param int $userId
     */
    public function markCustomerAsExported(int $userId): void
    {
        /** @var \App\Model\Customer\User\CustomerUser $customerUser */
        $customerUser = $this->getCustomerUserById($userId);
        $customerUser->markAsExported();

        $this->em->flush($customerUser);
    }

    /**
     * @param int $userId
     */
    public function markCustomerAsFailedExported(int $userId): void
    {
        /** @var \App\Model\Customer\User\CustomerUser $customerUser */
        $customerUser = $this->getCustomerUserById($userId);
        $customerUser->markAsFailedExported();

        $this->flush($customerUser);
    }

    /**
     * @param int $limit
     * @return \App\Model\Customer\User\CustomerUser[]
     */
    public function getCustomersWithoutDeliveryAddress(int $limit): array
    {
        return $this->customerUserRepository->getCustomersWithoutDeliveryAddress($limit);
    }

    /**
     * @param \App\Model\Customer\User\CustomerUser $customerUser
     */
    public function flush(CustomerUser $customerUser): void
    {
        $this->em->flush($customerUser);
    }

    /**
     * @param \App\Model\Customer\User\CustomerUser $customerUser
     * @param string $transferId
     * @return \App\Model\Customer\User\CustomerUser
     */
    public function editCustomerTransferId(CustomerUser $customerUser, string $transferId): CustomerUser
    {
        $customerUser->setTransferId($transferId);
        $this->em->flush($customerUser);

        if (!$this->userTransferIdFacade->isTransferIdExists($customerUser, $transferId)) {
            $userTransferIdData = $this->userTransferIdDataFactory->createFromCustomerTransferId($customerUser, $transferId);
            $this->userTransferIdFacade->create($userTransferIdData);
        }

        return $customerUser;
    }

    /**
     * @param \App\Model\Customer\TransferIds\UserTransferId $transferId
     * @param float $discountByCoefficientForEan
     */
    public function updateTransferIdAndPricingGroup(UserTransferId $transferId, float $discountByCoefficientForEan): void
    {
        $newPricingGroup = $this->pricingGroupFacade->findByDiscount($discountByCoefficientForEan, $transferId->getCustomer()->getDomainId());

        if ($newPricingGroup === null) {
            return;
        }

        $customerId = $transferId->getCustomer()->getId();
        $customer = $this->getCustomerUserById($customerId);
        $customer->updateTransferIdAndPricingGroup($transferId, $newPricingGroup);
        $this->flush($customer);
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $currentPricingGroup
     * @param float|null $coefficient
     * @param \App\Model\Customer\TransferIds\UserTransferId $userTransferId
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
     * @return \App\Model\Customer\User\CustomerUser[]
     */
    public function getForPricingGroupUpdate(): array
    {
        return $this->customerUserRepository->getForPricingGroupUpdate();
    }

    /**
     * @param \App\Model\Customer\User\CustomerUser $customerUser
     */
    public function changeCustomerPricingGroupUpdatedAt(CustomerUser $customerUser): void
    {
        $customerUser = $this->customerUserRepository->getCustomerUserById($customerUser->getId());
        $customerUserUpdateData = $this->customerUserUpdateDataFactory->createFromCustomerUser($customerUser);

        /** @var \App\Model\Customer\User\CustomerUserData $customerUserData */
        $customerUserData = $customerUserUpdateData->customerUserData;
        $customerUserData->pricingGroupUpdatedAt = new DateTime();

        $customerUserUpdateData->customerUserData = $customerUserData;

        $this->editByCustomerUser($customerUser->getId(), $customerUserUpdateData);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData $customerUserUpdateData
     * @return \App\Model\Customer\User\CustomerUser
     */
    public function registerCustomer(CustomerUserUpdateData $customerUserUpdateData): CustomerUser
    {
        /** @var \App\Model\Customer\User\CustomerUserData $customerUserData */
        $customerUserData = $customerUserUpdateData->customerUserData;

        $billingAddress = $this->billingAddressFactory->create($customerUserUpdateData->billingAddressData);
        $deliveryAddress = null;
        $deliveryAddressData = $customerUserUpdateData->deliveryAddressData;
        if ($customerUserData->memberOfLoyaltyProgram || $deliveryAddressData->addressFilled === true) {
            $deliveryAddressData->addressFilled = true;
            $deliveryAddress = $this->deliveryAddressFactory->create($customerUserUpdateData->deliveryAddressData);
            $this->em->persist($deliveryAddress);
        }

        /** @var \App\Model\Customer\User\CustomerUser $customerUser */
        $customerUser = $this->customerUserFactory->create(
            $customerUserUpdateData->customerUserData,
            $deliveryAddress
        );

        $this->em->persist($billingAddress);
        $this->em->persist($customerUser);
        $this->em->flush();

        return $customerUser;
    }
}
