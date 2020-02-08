<?php

declare(strict_types=1);

namespace App\Model\Security;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Administrator\Activity\AdministratorActivityFacade;
use Shopsys\FrameworkBundle\Model\Administrator\Administrator;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser;
use Shopsys\FrameworkBundle\Model\Order\OrderFlowFacade;
use Shopsys\FrameworkBundle\Model\Security\LoginListener as BaseLoginListener;
use Shopsys\FrameworkBundle\Model\Security\TimelimitLoginInterface;
use Shopsys\FrameworkBundle\Model\Security\UniqueLoginInterface;
use \App\Model\Customer\User\CustomerUserFacade;
use App\Model\Customer\Transfer\CustomerTransferService;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginListener extends BaseLoginListener
{
    /**
     * @var \App\Model\Customer\Transfer\CustomerTransferService
     */
    private $customerTransferService;

    /**
     * @var \App\Model\Customer\User\CustomerUserFacade
     */
    private $customerUserFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderFlowFacade $orderFlowFacade
     * @param \Shopsys\FrameworkBundle\Model\Administrator\Activity\AdministratorActivityFacade $administratorActivityFacade
     * @param \App\Model\Customer\Transfer\CustomerTransferService $customerTransferService
     * @param \App\Model\Customer\User\CustomerUserFacade $customerUserFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        OrderFlowFacade $orderFlowFacade,
        AdministratorActivityFacade $administratorActivityFacade,
        CustomerTransferService $customerTransferService,
        CustomerUserFacade $customerUserFacade
    ) {
        parent::__construct($em, $orderFlowFacade, $administratorActivityFacade);

        $this->customerTransferService = $customerTransferService;
        $this->customerUserFacade = $customerUserFacade;
    }

    /**
     * @param \Symfony\Component\Security\Http\Event\InteractiveLoginEvent $event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $token = $event->getAuthenticationToken();
        $customerUser = $token->getUser();

        if ($customerUser instanceof TimelimitLoginInterface) {
            $customerUser->setLastActivity(new DateTime());
        }

        if ($customerUser instanceof User) {
            $customerUser->onLogin();
        }

        if ($customerUser instanceof UniqueLoginInterface && !$customerUser->isMultidomainLogin()) {
            $customerUser->setLoginToken(uniqid('', true));
        }

        if ($customerUser instanceof Administrator) {
            $this->administratorActivityFacade->create(
                $customerUser,
                $event->getRequest()->getClientIp()
            );
        }

        $this->em->flush();

        try {
            /** @var \App\Model\Customer\User\CustomerUser $customer */
            $customer = $event->getAuthenticationToken()->getUser();

            foreach ($customer->getUserTransferId() as $transferId) {
                $customerInfoResponseItemData = $this->customerTransferService->getTransferItemsFromResponse($transferId, $customer->getDomainId());
                if ($customerInfoResponseItemData !== null) {
                    $this->customerUserFacade->updatePricingGroupByIsResponse(
                        $customerInfoResponseItemData->getTransferId()->getCustomer()->getPricingGroup(),
                        $customerInfoResponseItemData->getCoefficient(),
                        $customerInfoResponseItemData->getTransferId()
                    );
                }
            }
        } catch (\Throwable $throwable) {
        }
    }
}
