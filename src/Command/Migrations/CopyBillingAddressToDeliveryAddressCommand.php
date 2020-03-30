<?php

declare(strict_types=1);

namespace App\Command\Migrations;

use App\Model\Customer\DeliveryAddressDataFactory;
use App\Model\Customer\User\CustomerUserFacade;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressFacade;
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
     * @var \Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressFacade
     */
    protected $deliveryAddressFacade;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var \App\Model\Customer\User\CustomerUserFacade
     */
    private $customerUserFacade;

    /**
     * @var \App\Model\Customer\DeliveryAddressDataFactory
     */
    private $deliveryAddressDataFactory;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \App\Model\Customer\User\CustomerUserFacade $customerUserFacade
     * @param \App\Model\Customer\DeliveryAddressDataFactory $deliveryAddressDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressFacade $deliveryAddressFacade
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        CustomerUserFacade $customerUserFacade,
        DeliveryAddressDataFactory $deliveryAddressDataFactory,
        DeliveryAddressFacade $deliveryAddressFacade
    ) {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->customerUserFacade = $customerUserFacade;
        $this->deliveryAddressDataFactory = $deliveryAddressDataFactory;
        $this->deliveryAddressFacade = $deliveryAddressFacade;
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

            $users = $this->customerUserFacade->getCustomersWithoutDeliveryAddress(self::BATCH_LIMIT);
            $userCount = count($users);

            foreach ($users as $customerUser) {
                $deliveryAddressData = $this->deliveryAddressDataFactory->createFromBillingAddress($customerUser->getCustomer()->getBillingAddress());
                $this->deliveryAddressFacade->edit($customerUser->getDefaultDeliveryAddress()->getId(), $deliveryAddressData);

                $symfonyStyleIo->success(sprintf('Delivery address of user with ID `%s` has been created from user\'s billing address', $customerUser->getId()));
            }

            $this->entityManager->commit();
            $this->entityManager->clear();
        } while ($userCount > 0);

        return 0;
    }
}
