<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Model\GoPay;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\Plugin\Cron\SimpleCronModuleInterface;
use Shopsys\ShopBundle\Model\GoPay\Exception\GoPayPaymentDownloadException;
use Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethodFacade;
use Symfony\Bridge\Monolog\Logger;

class GoPayAvailablePaymentsCronModule implements SimpleCronModuleInterface
{
    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $logger;

    /**
     * @var \Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethodFacade
     */
    private $paymentMethodFacade;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethodFacade $paymentMethodFacade
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        GoPayPaymentMethodFacade $paymentMethodFacade,
        EntityManagerInterface $entityManager,
        Domain $domain
    ) {
        $this->paymentMethodFacade = $paymentMethodFacade;
        $this->em = $entityManager;
        $this->domain = $domain;
    }

    /**
     * @param \Symfony\Bridge\Monolog\Logger $logger
     */
    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function run(): void
    {
        try {
            $this->em->beginTransaction();
            $this->downloadAndUpdatePaymentMethodsForAllDomains();
            $this->em->commit();
        } catch (\Shopsys\ShopBundle\Model\GoPay\Exception\GoPayNotConfiguredException $exception) {
            $this->logger->addAlert('GoPay configuration is not set.');
        } catch (\Exception $exception) {
            $this->logger->addError($exception->getMessage(), ['exception' => $exception]);
            $this->em->rollback();
            throw $exception;
        }
    }

    private function downloadAndUpdatePaymentMethodsForAllDomains(): void
    {
        $allDomains = $this->domain->getAll();
        foreach ($allDomains as $domain) {
            $this->logger->addInfo(sprintf('downloading for %s locale', $domain->getLocale()));
            try {
                $this->paymentMethodFacade->downloadAndUpdatePaymentMethods($domain);
            } catch (GoPayPaymentDownloadException $ex) {
                $this->logger->addError($ex);
            }
        }
    }
}
