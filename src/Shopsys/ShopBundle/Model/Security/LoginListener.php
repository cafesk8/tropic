<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Security;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Administrator\Activity\AdministratorActivityFacade;
use Shopsys\FrameworkBundle\Model\Administrator\Administrator;
use Shopsys\FrameworkBundle\Model\Customer\User;
use Shopsys\FrameworkBundle\Model\Order\OrderFlowFacade;
use Shopsys\FrameworkBundle\Model\Security\LoginListener as BaseLoginListener;
use Shopsys\FrameworkBundle\Model\Security\TimelimitLoginInterface;
use Shopsys\FrameworkBundle\Model\Security\UniqueLoginInterface;
use Shopsys\ShopBundle\Model\Customer\CustomerFacade;
use Shopsys\ShopBundle\Model\Customer\Transfer\CustomerTransferService;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginListener extends BaseLoginListener
{
    /**
     * @var \Shopsys\ShopBundle\Model\Customer\Transfer\CustomerTransferService
     */
    private $customerTransferService;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\CustomerFacade
     */
    private $customerFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderFlowFacade $orderFlowFacade
     * @param \Shopsys\FrameworkBundle\Model\Administrator\Activity\AdministratorActivityFacade $administratorActivityFacade
     * @param \Shopsys\ShopBundle\Model\Customer\Transfer\CustomerTransferService $customerTransferService
     * @param \Shopsys\ShopBundle\Model\Customer\CustomerFacade $customerFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        OrderFlowFacade $orderFlowFacade,
        AdministratorActivityFacade $administratorActivityFacade,
        CustomerTransferService $customerTransferService,
        CustomerFacade $customerFacade
    ) {
        parent::__construct($em, $orderFlowFacade, $administratorActivityFacade);

        $this->customerTransferService = $customerTransferService;
        $this->customerFacade = $customerFacade;
    }

    /**
     * @param \Symfony\Component\Security\Http\Event\InteractiveLoginEvent $event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $token = $event->getAuthenticationToken();
        $user = $token->getUser();

        if ($user instanceof TimelimitLoginInterface) {
            $user->setLastActivity(new DateTime());
        }

        if ($user instanceof User) {
            $user->onLogin();
        }

        if ($user instanceof UniqueLoginInterface && !$user->isMultidomainLogin()) {
            $user->setLoginToken(uniqid('', true));
        }

        if ($user instanceof Administrator) {
            $this->administratorActivityFacade->create(
                $user,
                $event->getRequest()->getClientIp()
            );
        }

        $this->em->flush();

        try {
            /** @var \Shopsys\ShopBundle\Model\Customer\User $customer */
            $customer = $event->getAuthenticationToken()->getUser();

            foreach ($customer->getUserTransferIdAndEan() as $transferIdAndEan) {
                $customerInfoResponseItemData = $this->customerTransferService->getTransferItemsFromResponse($transferIdAndEan);
                if ($customerInfoResponseItemData !== null) {
                    $this->customerFacade->updatePricingGroupByIsResponse(
                        $customerInfoResponseItemData->getTransferIdAndEan()->getCustomer()->getPricingGroup(),
                        $customerInfoResponseItemData->getCoefficient(),
                        $customerInfoResponseItemData->getTransferIdAndEan()
                    );
                }
            }
        } catch (\Throwable $throwable) {
        }
    }
}
