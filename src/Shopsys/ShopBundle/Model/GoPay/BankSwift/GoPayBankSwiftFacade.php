<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Model\GoPay\BankSwift;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethodRepository;

class GoPayBankSwiftFacade
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethodRepository
     */
    private $goPayPaymentMethodRepository;

    /**
     * @var \Shopsys\ShopBundle\Model\GoPay\BankSwift\GoPayBankSwiftDataFactory
     */
    private $goPayBankSwiftDataFactory;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethodRepository $goPayPaymentMethodRepository
     * @param \Shopsys\ShopBundle\Model\GoPay\BankSwift\GoPayBankSwiftDataFactory $goPayBankSwiftDataFactory
     */
    public function __construct(
        EntityManagerInterface $em,
        GoPayPaymentMethodRepository $goPayPaymentMethodRepository,
        GoPayBankSwiftDataFactory $goPayBankSwiftDataFactory
    ) {
        $this->em = $em;
        $this->goPayPaymentMethodRepository = $goPayPaymentMethodRepository;
        $this->goPayBankSwiftDataFactory = $goPayBankSwiftDataFactory;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\GoPay\BankSwift\GoPayBankSwiftData $goPayBankSwiftData
     * @return \Shopsys\ShopBundle\Model\GoPay\BankSwift\GoPayBankSwift
     */
    public function create(GoPayBankSwiftData $goPayBankSwiftData): GoPayBankSwift
    {
        $bankSwift = new GoPayBankSwift($goPayBankSwiftData);
        $this->em->persist($bankSwift);
        $this->em->flush($bankSwift);

        return $bankSwift;
    }

    /**
     * @param int $currencyId
     * @return \Shopsys\ShopBundle\Model\GoPay\BankSwift\GoPayBankSwift[]
     */
    public function getAllByCurrencyId(int $currencyId): array
    {
        return $this->goPayPaymentMethodRepository->getBankSwiftsByCurrencyId($currencyId);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\GoPay\BankSwift\GoPayBankSwift $goPayBankSwift
     * @param array $swiftRawData
     */
    public function edit(GoPayBankSwift $goPayBankSwift, array $swiftRawData): void
    {
        $goPayBankSwiftData = $this->goPayBankSwiftDataFactory->createFromGoPayBankSwift($goPayBankSwift);
        $this->setGoPayBankSwiftDataFromSwiftRawData($goPayBankSwiftData, $swiftRawData);

        $goPayBankSwift->edit($goPayBankSwiftData);
        $this->em->flush($goPayBankSwift);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\GoPay\BankSwift\GoPayBankSwiftData $goPayBankSwiftData
     * @param array $swiftRawData
     */
    public function setGoPayBankSwiftDataFromSwiftRawData(GoPayBankSwiftData $goPayBankSwiftData, array $swiftRawData): void
    {
        $goPayBankSwiftData->swift = $swiftRawData['swift'];
        $goPayBankSwiftData->name = $swiftRawData['label']['cs']; // GoPay doesn't support Slovak names
        $goPayBankSwiftData->imageNormalUrl = $swiftRawData['image']['normal'];
        $goPayBankSwiftData->imageLargeUrl = $swiftRawData['image']['large'];
        $goPayBankSwiftData->isOnline = (bool)$swiftRawData['isOnline'];
    }
}
