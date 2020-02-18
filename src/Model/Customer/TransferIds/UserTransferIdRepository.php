<?php

declare(strict_types=1);

namespace App\Model\Customer\TransferIds;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser;

class UserTransferIdRepository
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getUserTransferIdRepository(): ObjectRepository
    {
        return $this->em->getRepository(UserTransferId::class);
    }

    /**
     * @param \App\Model\Customer\User\CustomerUser $customer
     * @param string $transferId
     * @return bool
     */
    public function isTransferIdExists(CustomerUser $customer, string $transferId)
    {
        $userTransferId = $this->getUserTransferIdRepository()->findOneBy([
            'customer' => $customer,
            'transferId' => $transferId,
        ]);

        return $userTransferId !== null;
    }
}
