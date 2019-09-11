<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\ShopBundle\Model\Customer\User;

class UserTransferIdAndEanFacade
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEanRepository
     */
    private $userTransferIdAndEanRepository;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEanFactory
     */
    private $userTransferIdAndEanFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEanDataFactory
     */
    private $userTransferIdAndEanDataFactory;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEanRepository $userTransferIdAndEanRepository
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEanDataFactory $userTransferIdAndEanDataFactory
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEanFactory $userTransferIdAndEanFactory
     */
    public function __construct(
        EntityManagerInterface $em,
        UserTransferIdAndEanRepository $userTransferIdAndEanRepository,
        UserTransferIdAndEanDataFactory $userTransferIdAndEanDataFactory,
        UserTransferIdAndEanFactory $userTransferIdAndEanFactory
    ) {
        $this->em = $em;
        $this->userTransferIdAndEanRepository = $userTransferIdAndEanRepository;
        $this->userTransferIdAndEanDataFactory = $userTransferIdAndEanDataFactory;
        $this->userTransferIdAndEanFactory = $userTransferIdAndEanFactory;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEanData $userTransferIdAndEanData
     */
    public function create(UserTransferIdAndEanData $userTransferIdAndEanData): void
    {
        $userTransferIdAndEan = $this->userTransferIdAndEanFactory->create($userTransferIdAndEanData);

        $this->em->persist($userTransferIdAndEan);
        $this->em->flush($userTransferIdAndEan);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\User $customer
     * @param string $transferId
     * @param mixed $ean
     * @return bool
     */
    public function isTransferIdAndEanExists(User $customer, string $transferId, $ean): bool
    {
        return $this->userTransferIdAndEanRepository->isTransferIdAndEanExists($customer, $transferId, $ean);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\User $customer
     * @param array $eans
     * @param string $transferId
     */
    public function saveTransferIdsAndEans(User $customer, array $eans, string $transferId): void
    {
        foreach ($eans as $ean) {
            if ($this->isTransferIdAndEanExists($customer, $transferId, $ean) === true) {
                continue;
            }

            $userTransferIdAndEanData = $this->userTransferIdAndEanDataFactory->createFromCustomerAndTransferIdAndEan($customer, $transferId, $ean);
            $this->create($userTransferIdAndEanData);
        }
    }
}
