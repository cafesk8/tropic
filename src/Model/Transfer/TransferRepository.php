<?php

declare(strict_types=1);

namespace App\Model\Transfer;

use App\Model\Transfer\Exception\TransferNotFoundException;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;

class TransferRepository
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
    private function getRepository(): ObjectRepository
    {
        return $this->em->getRepository(Transfer::class);
    }

    /**
     * @param string $identifier
     * @return \App\Model\Transfer\Transfer
     */
    public function getByIdentifier(string $identifier): Transfer
    {
        $transfer = $this->getRepository()->findOneBy(['identifier' => $identifier]);
        if ($transfer === null) {
            throw new TransferNotFoundException('Transfer with identifier "' . $identifier . '" was not found.');
        }

        return $transfer;
    }

    /**
     * @return \App\Model\Transfer\Transfer[]
     */
    public function getAll(): array
    {
        return $this->getRepository()->findBy([], ['identifier' => 'asc']);
    }
}
