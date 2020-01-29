<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer\TransferIds;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\ShopBundle\Model\Customer\User;

class UserTransferIdFacade
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\TransferIds\UserTransferIdRepository
     */
    private $userTransferIdRepository;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\TransferIds\UserTransferIdFactory
     */
    private $userTransferIdFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\TransferIds\UserTransferIdDataFactory
     */
    private $userTransferIdDataFactory;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIds\UserTransferIdRepository $userTransferIdRepository
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIds\UserTransferIdDataFactory $userTransferIdDataFactory
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIds\UserTransferIdFactory $userTransferIdFactory
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
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIds\UserTransferIdData $userTransferIdData
     */
    public function create(UserTransferIdData $userTransferIdData): void
    {
        $userTransferId = $this->userTransferIdFactory->create($userTransferIdData);

        $this->em->persist($userTransferId);
        $this->em->flush($userTransferId);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\User $customer
     * @param string $transferId
     * @return bool
     */
    public function isTransferIdExists(User $customer, string $transferId): bool
    {
        return $this->userTransferIdRepository->isTransferIdExists($customer, $transferId);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\User $customer
     * @param string $transferId
     */
    public function saveTransferIds(User $customer, string $transferId): void
    {
        if (!$this->isTransferIdExists($customer, $transferId)) {
            $userTransferIdData = $this->userTransferIdDataFactory->createFromCustomerTransferId($customer, $transferId);
            $this->create($userTransferIdData);
        }
    }
}
