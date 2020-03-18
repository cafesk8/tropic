<?php

declare(strict_types=1);

namespace App\Model\Order\GiftCertificate;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class OrderGiftCertificateRepository
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

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
    protected function getOrderGiftCertificateRepository(): EntityRepository
    {
        return $this->em->getRepository(OrderGiftCertificate::class);
    }
}
