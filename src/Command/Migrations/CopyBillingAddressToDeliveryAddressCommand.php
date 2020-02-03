<?php

declare(strict_types=1);

namespace App\Command\Migrations;

use Doctrine\ORM\EntityManagerInterface;
use App\Model\Customer\CustomerFacade;
use App\Model\Customer\DeliveryAddressDataFactory;
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
     * @var \App\Model\Customer\CustomerFacade
     */
    private $customerFacade;

    /**
     * @var \App\Model\Customer\DeliveryAddressDataFactory
     */
    private $deliveryAddressDataFactory;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \App\Model\Customer\CustomerFacade $customerFacade
     * @param \App\Model\Customer\DeliveryAddressDataFactory $deliveryAddressDataFactory
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        CustomerFacade $customerFacade,
        DeliveryAddressDataFactory $deliveryAddressDataFactory
    ) {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->customerFacade = $customerFacade;
        $this->deliveryAddressDataFactory = $deliveryAddressDataFactory;
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $symfonyStyleIo = new SymfonyStyle($input, $output);

        do {
            $this->entityManager->beginTransaction();

            $users = $this->customerFacade->getCustomersWithoutDeliveryAddress(self::BATCH_LIMIT);
            $userCount = count($users);

            foreach ($users as $user) {
                $deliveryAddressData = $this->deliveryAddressDataFactory->createFromBillingAddress($user->getBillingAddress());
                $this->customerFacade->editDeliveryAddress($user, $deliveryAddressData);

                $this->customerFacade->flush($user);

                $symfonyStyleIo->success(sprintf('Delivery address of user with ID `%s` has been created from user\'s billing address', $user->getId()));
            }

            $this->entityManager->commit();
            $this->entityManager->clear();
        } while ($userCount > 0);

        return 0;
    }
}
