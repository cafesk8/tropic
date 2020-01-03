<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Front;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\ShopBundle\Component\Redis\RedisFacade;
use Symfony\Component\HttpFoundation\Response;

class HealtzController extends FrontBaseController
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * @var \Shopsys\ShopBundle\Component\Redis\RedisFacade
     */
    private $redisFacade;

    /**
     * @var \Swift_Mailer
     */
    private $swiftMailer;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Shopsys\ShopBundle\Component\Redis\RedisFacade $redisFacade
     * @param \Swift_Mailer $swiftMailer
     */
    public function __construct(EntityManagerInterface $entityManager, RedisFacade $redisFacade, \Swift_Mailer $swiftMailer)
    {
        $this->entityManager = $entityManager;
        $this->redisFacade = $redisFacade;
        $this->swiftMailer = $swiftMailer;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(): Response
    {
        if ($this->entityManager->getConnection()->ping() === false
            || $this->swiftMailer->getTransport()->ping() === false
        ) {
            return new Response('', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        // Because of weird behaviour of redis you cannot catch exception here and redis will always throw error 500
        $this->redisFacade->pingAllClients();

        return new Response('', Response::HTTP_OK);
    }
}
