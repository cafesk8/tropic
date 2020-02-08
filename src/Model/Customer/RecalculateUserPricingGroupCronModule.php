<?php

declare(strict_types=1);

namespace App\Model\Customer;

use DateInterval;
use DateTime;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade;
use Shopsys\Plugin\Cron\SimpleCronModuleInterface;
use App\Model\Order\OrderFacade;
use Symfony\Bridge\Monolog\Logger;

class RecalculateUserPricingGroupCronModule implements SimpleCronModuleInterface
{
    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $logger;

    /**
     * @var \App\Model\Order\OrderFacade
     */
    private $orderFacade;

    /**
     * @var \App\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @var \App\Model\Customer\User\CustomerUserFacade
     */
    private $customerUserFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade
     */
    private $pricingGroupSettingFacade;

    /**
     * @param \App\Model\Order\OrderFacade $orderFacade
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \App\Model\Customer\User\CustomerUserFacade $customerUserFacade
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade $pricingGroupSettingFacade
     */
    public function __construct(OrderFacade $orderFacade, PricingGroupFacade $pricingGroupFacade, CustomerUserFacade $customerUserFacade, PricingGroupSettingFacade $pricingGroupSettingFacade)
    {
        $this->orderFacade = $orderFacade;
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->customerUserFacade = $customerUserFacade;
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
        $today = new DateTime('today');
        $tomorrow = new DateTime('tomorrow');

        $startTime = $today->sub(DateInterval::createFromDateString('21 days'));
        $endTime = $tomorrow->sub(DateInterval::createFromDateString('14 days'));

        $customerIds = $this->orderFacade->getCustomerIdsFromOrdersByDatePeriod($startTime, $endTime);
        $pricingGroupsIndexedByDomainId = $this->pricingGroupFacade->getAllIndexedByDomainIdOrderedByMinimalPrice();
        $customers = $this->customerUserFacade->getUsersByIds($customerIds);
        $ordersValueIndexedByUser = $this->orderFacade->getOrdersValueIndexedByCustomerIdOlderThanDate($customerIds, $endTime);

        /** @var \App\Model\Customer\User\CustomerUser $customer */
        foreach ($customers as $customer) {
            $pricingGroupsForDomain = $pricingGroupsIndexedByDomainId[$customer->getDomainId()];
            $newPricingGroupForCustomer = null;

            /** @var \App\Model\Pricing\Group\PricingGroup $pricingGroup */
            foreach ($pricingGroupsForDomain as $pricingGroup) {
                if ($pricingGroup->getMinimalPrice() !== null
                    && $ordersValueIndexedByUser[$customer->getId()]->isGreaterThan($pricingGroup->getMinimalPrice())
                ) {
                    $newPricingGroupForCustomer = $pricingGroup;
                }
            }

            if ($newPricingGroupForCustomer !== null) {
                $this->customerUserFacade->changePricingGroup($customer, $newPricingGroupForCustomer);
            } else {
                $this->customerUserFacade->changePricingGroup($customer, $this->pricingGroupSettingFacade->getDefaultPricingGroupByDomainId($customer->getDomainId()));
            }

            $this->logger->addInfo(sprintf('Pricing group for user with id `%s` was changed.', $customer->getId()));
        }
    }
}
