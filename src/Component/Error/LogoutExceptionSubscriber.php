<?php

declare(strict_types=1);

namespace App\Component\Error;

use App\Component\FlashMessage\FlashMessageSender;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\LogoutException;

class LogoutExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * @var \App\Component\FlashMessage\FlashMessageSender
     */
    private $flashMessageSender;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser
     */
    private $currentCustomerUser;

    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    private $router;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser
     * @param \Symfony\Component\Routing\RouterInterface $router
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Component\FlashMessage\FlashMessageSender $flashMessageSender
     */
    public function __construct(CurrentCustomerUser $currentCustomerUser, RouterInterface $router, Domain $domain, FlashMessageSender $flashMessageSender)
    {
        $this->currentCustomerUser = $currentCustomerUser;
        $this->router = $router;
        $this->domain = $domain;
        $this->flashMessageSender = $flashMessageSender;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [
                ['processException', 10],
            ],
        ];
    }

    /**
     * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
     */
    public function processException(GetResponseForExceptionEvent $event): void
    {
        if ($event->getException() instanceof LogoutException) {
            if ($this->currentCustomerUser->findCurrentCustomerUser() !== null) {
                $domainId = $this->currentCustomerUser->findCurrentCustomerUser()->getDomainId();
                $locale = $this->domain->getDomainConfigById($domainId)->getLocale();

                $this->flashMessageSender->addErrorFlash(t('Při pokusu o odhlášení došlo k problému. Pokud se opravdu chcete odhlásit, prosím, zkuste to ještě jednou.', [], 'messages', $locale));
            }

            $referer = $event->getRequest()->headers->get('referer');
            $event->setResponse(new RedirectResponse($referer == null ? $this->router->generate('front_homepage') : $referer));
        }
    }
}
