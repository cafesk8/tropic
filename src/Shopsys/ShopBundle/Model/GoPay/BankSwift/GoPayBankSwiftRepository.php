<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Model\GoPay\BankSwift;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethod;

class GoPayBankSwiftRepository
{
    /**
     * @var \Doctrine\ORM\EntityManager
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
    private function getBankSwiftRepository(): EntityRepository
    {
        return $this->em->getRepository(GoPayBankSwift::class);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethod $paymentMethod
     * @return \Shopsys\ShopBundle\Model\GoPay\BankSwift\GoPayBankSwift[]
     */
    public function getAllIndexedBySwiftByPaymentMethod(GoPayPaymentMethod $paymentMethod): array
    {
        return $this->getBankSwiftRepository()
            ->createQueryBuilder('bs')
            ->indexBy('bs', 'bs.swift')
            ->where('bs.goPayPaymentMethod = :paymentMethod')
            ->setParameter('paymentMethod', $paymentMethod)
            ->getQuery()
            ->execute();
    }
}
