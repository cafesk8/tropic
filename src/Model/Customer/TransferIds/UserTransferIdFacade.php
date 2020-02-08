<?php

declare(strict_types=1);

namespace App\Model\Customer\TransferIds;

use Doctrine\ORM\EntityManagerInterface;
use App\Model\Customer\User\CustomerUser;

class UserTransferIdFacade
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator
     */
    private $em;

    /**
     * @var \App\Model\Customer\TransferIds\UserTransferIdRepository
     */
    private $userTransferIdRepository;

    /**
     * @var \App\Model\Customer\TransferIds\UserTransferIdFactory
     */
    private $userTransferIdFactory;

    /**
     * @var \App\Model\Customer\TransferIds\UserTransferIdDataFactory
     */
    private $userTransferIdDataFactory;

    /**
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
     * @param \App\Model\Customer\TransferIds\UserTransferIdRepository $userTransferIdRepository
     * @param \App\Model\Customer\TransferIds\UserTransferIdDataFactory $userTransferIdDataFactory
     * @param \App\Model\Customer\TransferIds\UserTransferIdFactory $userTransferIdFactory
     */
    public function __construct(
        EntityManagerInterface $em,
        UserTransferIdRepository $userTransferIdRepository,
        UserTransferIdDataFactory $userTransferIdDataFactory,
        UserTransferIdFactory $userTransferIdFactory
    ) {
        $this->em = $em;
        $this->userTransferIdRepository = $userTransferIdRepository;
        $this->userTransferIdDataFactory = $userTransferIdDataFactory;
        $this->userTransferIdFactory = $userTransferIdFactory;
    }

    /**
     * @param \App\Model\Customer\TransferIds\UserTransferIdData $userTransferIdData
     */
    public function create(UserTransferIdData $userTransferIdData): void
    {
        $userTransferId = $this->userTransferIdFactory->create($userTransferIdData);

        $this->em->persist($userTransferId);
        $this->em->flush($userTransferId);
    }

    /**
     * @param \App\Model\Customer\User\CustomerUser $customer
     * @param string $transferId
     * @return bool
     */
    public function isTransferIdExists(CustomerUser $customer, string $transferId): bool
    {
        return $this->userTransferIdRepository->isTransferIdExists($customer, $transferId);
    }

    /**
     * @param \App\Model\Customer\User\CustomerUser $customer
     * @param string $transferId
     */
    public function saveTransferIds(CustomerUser $customer, string $transferId): void
    {
        if (!$this->isTransferIdExists($customer, $transferId)) {
            $userTransferIdData = $this->userTransferIdDataFactory->createFromCustomerTransferId($customer, $transferId);
            $this->create($userTransferIdData);
        }
    }
}
