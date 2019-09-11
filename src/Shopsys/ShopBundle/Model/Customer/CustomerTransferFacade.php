<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer;

use Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\CustomerTransferService;

class CustomerTransferFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\CustomerTransferService
     */
    private $customerTransferService;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\CustomerFacade
     */
    private $customerFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\CustomerTransferService $customerTransferService
     * @param \Shopsys\ShopBundle\Model\Customer\CustomerFacade $customerFacade
     */
    public function __construct(CustomerTransferService $customerTransferService, CustomerFacade $customerFacade)
    {
        $this->customerTransferService = $customerTransferService;
        $this->customerFacade = $customerFacade;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\User $customer
     */
    public function updatePricingGroupFromIs(User $customer): void
    {
        foreach ($customer->getUserTransferIdAndEan() as $transferIdAndEan) {
            $responseData = $this->customerTransferService->getCustomersInfoResponse($customer, $transferIdAndEan->getEan());

            if ($this->customerTransferService->isCoeffListAttributeCorrect($responseData)) {
                return;
            }

            $coefListAttribute = $responseData['CoeffList'][0];
            if ($this->customerTransferService->isCoefficientAttributeCorrect($coefListAttribute)) {
                return;
            }

            /** @var float $discountByCoefficientForEan */
            $discountByCoefficientForEan = $coefListAttribute['Coefficient'];

            /** @var \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup $currentPricingGroup */
            $currentPricingGroup = $customer->getPricingGroup();

            if ($discountByCoefficientForEan > $currentPricingGroup->getDiscount()) {
                $this->customerFacade->updateTransferIdAndEanAndPricingGroup($customer, $transferIdAndEan, $discountByCoefficientForEan);
            }
        }
    }
}
