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
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginListener extends BaseLoginListener
{
    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderFlowFacade $orderFlowFacade
     * @param \Shopsys\FrameworkBundle\Model\Administrator\Activity\AdministratorActivityFacade $administratorActivityFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        OrderFlowFacade $orderFlowFacade,
        AdministratorActivityFacade $administratorActivityFacade
    ) {
        parent::__construct($em, $orderFlowFacade, $administratorActivityFacade);
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

        if ($customerUser instanceof CustomerUser) {
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
    }
}
