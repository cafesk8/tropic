<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transfer;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class TransferFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Transfer\TransferRepository
     */
    private $transferRepository;

    /**
     * @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator
     */
    private $em;

    /**
     * @param \Shopsys\ShopBundle\Model\Transfer\TransferRepository $transferRepository
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
     */
    public function __construct(
        TransferRepository $transferRepository,
        EntityManagerInterface $em
    ) {
        $this->transferRepository = $transferRepository;
        $this->em = $em;
    }

    /**
     * @param string $identifier
     */
    public function setAsInProgress(string $identifier): void
    {
        $transfer = $this->transferRepository->getByIdentifier($identifier);
        $transfer->setAsInProgress();
        $this->em->flush($transfer);
    }

    /**
     * @param string $identifier
     * @param \DateTime $lastStartAt
     */
    public function setAsFinished(string $identifier, DateTime $lastStartAt): void
    {
        $transfer = $this->transferRepository->getByIdentifier($identifier);
        $transfer->setAsFinished($lastStartAt);
        $this->em->flush($transfer);
    }

    /**
     * @param string $identifier
     */
    public function resetTransferByTransferId(string $identifier): void
    {
        $transfer = $this->transferRepository->getByIdentifier($identifier);
        $transfer->setLastStartAt(null);
        $this->em->flush($transfer);
    }

    /**
     * @param string $identifier
     * @return bool
     */
    public function isEnabled(string $identifier): bool
    {
        $transfer = $this->transferRepository->getByIdentifier($identifier);

        return $transfer->isEnabled();
    }

    /**
     * @param string $identifier
     * @return \Shopsys\ShopBundle\Model\Transfer\Transfer
     */
    public function getByIdentifier(string $identifier): Transfer
    {
        return $this->transferRepository->getByIdentifier($identifier);
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Transfer\Transfer[]
     */
    public function getAll(): array
    {
        return $this->transferRepository->getAll();
    }
}
