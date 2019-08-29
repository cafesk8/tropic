<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Front;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\ShopBundle\Model\Gtm\GtmContainer;
use Symfony\Component\HttpFoundation\Response;

class GtmController extends FrontBaseController
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade
     */
    private $currencyFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\ShopBundle\Model\Gtm\GtmContainer
     */
    private $gtmContainer;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\ShopBundle\Model\Gtm\GtmContainer $gtmContainer
     */
    public function __construct(CurrencyFacade $currencyFacade, Domain $domain, GtmContainer $gtmContainer)
    {
        $this->currencyFacade = $currencyFacade;
        $this->domain = $domain;
        $this->gtmContainer = $gtmContainer;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function headAction(): Response
    {
        $currency = $this->currencyFacade->getDomainDefaultCurrencyByDomainId($this->domain->getId());

        return $this->render('@ShopsysShop/Front/Inline/MeasuringScript/Gtm/head.html.twig', [
            'currencyCode' => $currency->getCode(),
            'isGtmEnabled' => $this->gtmContainer->isEnabled(),
            'gtmContainerId' => $this->gtmContainer->getContainerId(),
            'gtmContainerEnvironment' => $this->gtmContainer->getContainerEnvironment(),
            'gtmDataLayer' => $this->gtmContainer->getDataLayer()->getData(),
            'gtmDataLayerPushes' => $this->gtmContainer->getDataLayer()->getPushes(),
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function bodyAction(): Response
    {
        return $this->render('@ShopsysShop/Front/Inline/MeasuringScript/Gtm/body.html.twig', [
            'isGtmEnabled' => $this->gtmContainer->isEnabled(),
            'gtmContainerId' => $this->gtmContainer->getContainerId(),
            'gtmContainerEnvironment' => $this->gtmContainer->getContainerEnvironment(),
        ]);
    }
}
