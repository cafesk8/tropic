<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer;

use Exception;
use Shopsys\Plugin\Cron\IteratedCronModuleInterface;
use Symfony\Bridge\Monolog\Logger;

class CustomerUpdatePricingGroupFromIsCronModule implements IteratedCronModuleInterface
{
    private const BATCH_SIZE = 100;

    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $logger;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\CustomerFacade
     */
    private $customerFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\CustomerTransferFacade
     */
    private $customerTransferFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\CustomerFacade $customerFacade
     * @param \Shopsys\ShopBundle\Model\Customer\CustomerTransferFacade $customerTransferFacade
     */
    public function __construct(CustomerFacade $customerFacade, CustomerTransferFacade $customerTransferFacade)
    {
        $this->customerFacade = $customerFacade;
        $this->customerTransferFacade = $customerTransferFacade;
    }

    /**
     * @param \Symfony\Bridge\Monolog\Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function wakeUp()
    {
    }

    public function iterate()
    {
        $customers = $this->customerFacade->getAllUsers();

        /** @var \Shopsys\ShopBundle\Model\Customer\User $customer */
        foreach ($customers as $idx => $customer) {
            try {
                $this->logger->addInfo(sprintf('Find pricing group for user with id `%s`.', $customer->getId()));
                $this->customerTransferFacade->updatePricingGroupFromIs($customer);

                if ($idx === self::BATCH_SIZE) {
                    return true;
                }
            } catch (Exception $exception) {
                $this->logger->addError(
                    sprintf(
                        'Updated pricing group for customer id %s was aborted. Reason of this error: %s',
                        $customer->getId(),
                        $exception->getMessage()
                    )
                );
            }
        }
    }

    public function sleep()
    {
    }
}
