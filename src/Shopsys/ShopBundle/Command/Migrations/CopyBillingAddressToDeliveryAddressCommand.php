<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Command\Migrations;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressFactory;
use Shopsys\ShopBundle\Model\Customer\CustomerFacade;
use Shopsys\ShopBundle\Model\Customer\DeliveryAddressDataFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CopyBillingAddressToDeliveryAddressCommand extends Command
{
    private const BATCH_LIMIT = 10;

    /**
     * @var string
     */
    protected static $defaultName = 'shopsys:copy:billing-address-to-delivery-address';

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\CustomerFacade
     */
    private $customerFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\DeliveryAddressDataFactory
     */
    private $deliveryAddressDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressFactory
     */
    private $deliveryAddressFactory;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Shopsys\ShopBundle\Model\Customer\CustomerFacade $customerFacade
     * @param \Shopsys\ShopBundle\Model\Customer\DeliveryAddressDataFactory $deliveryAddressDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressFactory $deliveryAddressFactory
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        CustomerFacade $customerFacade,
        DeliveryAddressDataFactory $deliveryAddressDataFactory,
        DeliveryAddressFactory $deliveryAddressFactory
    ) {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->customerFacade = $customerFacade;
        $this->deliveryAddressDataFactory = $deliveryAddressDataFactory;
        $this->deliveryAddressFactory = $deliveryAddressFactory;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Copy billing address to delivery address');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $symfonyStyleIo = new SymfonyStyle($input, $output);

        do {
            $this->entityManager->beginTransaction();

            $users = $this->customerFacade->getCustomersWithoutDeliveryAddress(self::BATCH_LIMIT);
            $userCount = count($users);

            foreach ($users as $user) {
                $deliveryAddressData = $this->deliveryAddressDataFactory->createFromBillingAddress($user->getBillingAddress());
                $user->editDeliveryAddress($deliveryAddressData, $this->deliveryAddressFactory);

                $this->customerFacade->flush($user);

                $symfonyStyleIo->success(sprintf('Delivery address of user with ID `%s` has been created from user\'s billing address', $user->getId()));
            }

            $this->entityManager->commit();
            $this->entityManager->clear();
        } while ($userCount > 0);
    }
}
