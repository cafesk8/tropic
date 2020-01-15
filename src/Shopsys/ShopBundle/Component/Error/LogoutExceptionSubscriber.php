<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Error;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\FlashMessage\FlashMessageSender;
use Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\LogoutException;

class LogoutExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\FlashMessage\FlashMessageSender
     */
    private $flashMessageSender;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer
     */
    private $currentCustomer;

    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    private $router;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \Shopsys\FrameworkBundle\Component\FlashMessage\FlashMessageSender $flashMessageSender
     * @param \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer $currentCustomer
     * @param \Symfony\Component\Routing\RouterInterface $router
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(FlashMessageSender $flashMessageSender, CurrentCustomer $currentCustomer, RouterInterface $router, Domain $domain)
    {
        $this->flashMessageSender = $flashMessageSender;
        $this->currentCustomer = $currentCustomer;
        $this->router = $router;
        $this->domain = $domain;
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
            if ($this->currentCustomer->findCurrentUser() !== null) {
                $domainId = $this->currentCustomer->findCurrentUser()->getDomainId();
                $locale = $this->domain->getDomainConfigById($domainId)->getLocale();

                $this->flashMessageSender->addErrorFlash(t('Při pokusu o odhlášení došlo k problému. Pokud se opravdu chcete odhlásit, prosím, zkuste to ještě jednou.', [], 'messages', $locale));
            }

            $referer = $event->getRequest()->headers->get('referer');
            $event->setResponse(new RedirectResponse($referer == null ? $this->router->generate('front_homepage') : $referer));
        }
    }
}
