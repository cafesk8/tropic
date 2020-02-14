<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use GoPay\Definition\Response\PaymentStatus;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use App\Model\GoPay\GoPayTransaction;
use App\Model\GoPay\GoPayTransactionData;

class GoPayTransactionDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
{
    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager): void
    {
        $referenceName = OrderDataFixture::ORDER_PREFIX . 1;
        $order = $this->getReference($referenceName);
        $transactionData = new GoPayTransactionData('3094521651', $order, PaymentStatus::CREATED);
        $goPayTransaction = new GoPayTransaction($transactionData);

        $manager->persist($goPayTransaction);
        $manager->flush();
    }

    /**
     * @inheritDoc
     */
    public function getDependencies(): array
    {
        return [
          OrderDataFixture::class,
        ];
    }
}
