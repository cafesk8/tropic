<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Customer\User;

class UserTransferIdAndEanRepository
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
    protected function getUserTransferIdAndEanRepository(): ObjectRepository
    {
        return $this->em->getRepository(UserTransferIdAndEan::class);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\User $customer
     * @param string $transferId
     * @param mixed $ean
     */
    public function isTransferIdAndEanExists(User $customer, string $transferId, $ean)
    {
        $userTransferIdAndEan = $this->getUserTransferIdAndEanRepository()->findOneBy([
            'customer' => $customer,
            'transferId' => $transferId,
            'ean' => $ean,
        ]);

        return $userTransferIdAndEan !== null;
    }
}
