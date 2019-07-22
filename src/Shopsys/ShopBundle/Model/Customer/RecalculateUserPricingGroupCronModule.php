<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer;

use DateTime;
use Shopsys\FrameworkBundle\Model\Customer\CustomerFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade;
use Shopsys\Plugin\Cron\SimpleCronModuleInterface;
use Shopsys\ShopBundle\Model\Order\OrderFacade;
use Symfony\Bridge\Monolog\Logger;

class RecalculateUserPricingGroupCronModule implements SimpleCronModuleInterface
{
    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $logger;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\OrderFacade
     */
    private $orderFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\CustomerFacade
     */
    private $customerFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade
     */
    private $pricingGroupSettingFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Order\OrderFacade $orderFacade
     * @param \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \Shopsys\ShopBundle\Model\Customer\CustomerFacade $customerFacade
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade $pricingGroupSettingFacade
     */
    public function __construct(OrderFacade $orderFacade, PricingGroupFacade $pricingGroupFacade, CustomerFacade $customerFacade, PricingGroupSettingFacade $pricingGroupSettingFacade)
    {
        $this->orderFacade = $orderFacade;
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->customerFacade = $customerFacade;
        $this->pricingGroupSettingFacade = $pricingGroupSettingFacade;
    }

    /**
     * @param \Symfony\Bridge\Monolog\Logger $logger
     */
    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * This method is called to run the CRON module.
     */
    public function run(): void
    {
        $startTime = new DateTime('today');
        $endTime = new DateTime('tomorrow');

        $customerIds = $this->orderFacade->getCustomerIdsFromOrdersUpdatedAt($startTime, $endTime);
        $pricingGroupsIndexedByDomainId = $this->pricingGroupFacade->getAllIndexedByDomainIdOrderedByMinimalPrice();
        $customers = $this->customerFacade->getUsersByIds($customerIds);
        $ordersValueIndexedByUser = $this->orderFacade->getOrdersValueIndexedByCustomerId($customerIds);

        /** @var \Shopsys\ShopBundle\Model\Customer\User $customer */
        foreach ($customers as $customer) {
            $pricingGroupsForDomain = $pricingGroupsIndexedByDomainId[$customer->getDomainId()];
            $newPricingGroupForCustomer = null;

            /** @var \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup */
            foreach ($pricingGroupsForDomain as $pricingGroup) {
                if ($pricingGroup->getMinimalPrice() !== null
                    && $ordersValueIndexedByUser[$customer->getId()]->isGreaterThan($pricingGroup->getMinimalPrice())
                ) {
                    $newPricingGroupForCustomer = $pricingGroup;
                }
            }

            if ($newPricingGroupForCustomer !== null) {
                $this->customerFacade->changePricingGroup($customer, $newPricingGroupForCustomer);
            } else {
                $this->customerFacade->changePricingGroup($customer, $this->pricingGroupSettingFacade->getDefaultPricingGroupByDomainId($customer->getDomainId()));
            }

            $this->logger->addInfo(sprintf('Pricing group for user with id `%s` was changed.', $customer->getId()));
        }
    }
}
