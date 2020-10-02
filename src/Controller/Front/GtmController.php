<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Component\Cofidis\CofidisFacade;
use App\Model\Gtm\GtmContainer;
use App\Model\Order\OrderFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;
use Symfony\Component\HttpFoundation\Response;

class GtmController extends FrontBaseController
{
    /**
     * @var \App\Model\Pricing\Currency\CurrencyFacade
     */
    private $currencyFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \App\Model\Gtm\GtmContainer
     */
    private $gtmContainer;

    private CofidisFacade $cofidisFacade;

    private OrderFacade $orderFacade;

    /**
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Gtm\GtmContainer $gtmContainer
     * @param \App\Component\Cofidis\CofidisFacade $cofidisFacade
     * @param \App\Model\Order\OrderFacade $orderFacade
     */
    public function __construct(
        CurrencyFacade $currencyFacade,
        Domain $domain,
        GtmContainer $gtmContainer,
        CofidisFacade $cofidisFacade,
        OrderFacade $orderFacade
    ) {
        $this->currencyFacade = $currencyFacade;
        $this->domain = $domain;
        $this->gtmContainer = $gtmContainer;
        $this->cofidisFacade = $cofidisFacade;
        $this->orderFacade = $orderFacade;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function headAction(): Response
    {
        $currency = $this->currencyFacade->getDomainDefaultCurrencyByDomainId($this->domain->getId());
        $data = $this->gtmContainer->getDataLayer()->getData();

        $variables = [
            'currencyCode' => $currency->getCode(),
            'isGtmEnabled' => $this->gtmContainer->isEnabled(),
            'gtmContainerId' => $this->gtmContainer->getContainerId(),
            'gtmContainerEnvironment' => $this->gtmContainer->getContainerEnvironment(),
            'gtmDataLayer' => $data,
            'gtmDataLayerPushes' => $this->gtmContainer->getDataLayer()->getPushes(),
        ];

        if (isset($data['ecommerce']['purchase']['actionField']['id'])) {
            $variables['cofidisPaymentLink'] = $this->cofidisFacade->getCofidisPaymentLink(
                $this->orderFacade->findByNumber($data['ecommerce']['purchase']['actionField']['id'])
            );
        }

        return $this->render('Front/Inline/MeasuringScript/Gtm/head.html.twig', $variables);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function bodyAction(): Response
    {
        return $this->render('Front/Inline/MeasuringScript/Gtm/body.html.twig', [
            'isGtmEnabled' => $this->gtmContainer->isEnabled(),
            'gtmContainerId' => $this->gtmContainer->getContainerId(),
            'gtmContainerEnvironment' => $this->gtmContainer->getContainerEnvironment(),
        ]);
    }
}
