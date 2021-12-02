<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Component\LuigisBox\LuigisBoxApiKeysProvider;
use App\Model\Order\Order;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Script\ScriptFacade;
use Symfony\Component\HttpFoundation\Response;

class ScriptController extends FrontBaseController
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Script\ScriptFacade
     */
    private $scriptFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    private string $shopId;

    private LuigisBoxApiKeysProvider $keysProvider;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Script\ScriptFacade $scriptFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Component\LuigisBox\LuigisBoxApiKeysProvider $keysProvider
     * @param string $shopId
     */
    public function __construct(
        ScriptFacade $scriptFacade,
        Domain $domain,
        LuigisBoxApiKeysProvider $keysProvider,
        string $shopId
    ) {
        $this->scriptFacade = $scriptFacade;
        $this->domain = $domain;
        $this->shopId = $shopId;
        $this->keysProvider = $keysProvider;
    }

    public function embedAllPagesScriptsAction()
    {
        return $this->render('Front/Inline/MeasuringScript/scripts.html.twig', [
            'scriptsCodes' => $this->scriptFacade->getAllPagesScriptCodes(),
        ]);
    }

    public function embedAllPagesGoogleAnalyticsScriptAction()
    {
        if (!$this->scriptFacade->isGoogleAnalyticsActivated($this->domain->getId())) {
            return new Response('');
        }

        return $this->render('Front/Inline/MeasuringScript/googleAnalytics.html.twig', [
            'trackingId' => $this->scriptFacade->getGoogleAnalyticsTrackingId($this->domain->getId()),
        ]);
    }

    /**
     * @param \App\Model\Order\Order $order
     */
    public function embedOrderSentPageScriptsAction(Order $order)
    {
        return $this->render('Front/Inline/MeasuringScript/scripts.html.twig', [
            'scriptsCodes' => $this->scriptFacade->getOrderSentPageScriptCodesWithReplacedVariables($order),
        ]);
    }

    /**
     * @param \App\Model\Order\Order $order
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function embedOrderSentPageGoogleAnalyticsScriptAction(Order $order): Response
    {
        if (!$this->scriptFacade->isGoogleAnalyticsActivated($this->domain->getId())) {
            return new Response('');
        }

        return $this->render('Front/Inline/MeasuringScript/googleAnalyticsEcommerce.html.twig', [
            'order' => $order,
        ]);
    }

    /**
     * @param \App\Model\Order\Order $order
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function embedOrderSentPageZboziScriptAction(Order $order): Response
    {
        return $this->render('Front/Inline/MeasuringScript/zbozi.html.twig', [
            'order' => $order,
            'shopId' => $this->shopId,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function embedAllPagesLuigisBoxScriptAction(): Response
    {
        return $this->render('Front/Inline/MeasuringScript/luigisBox.html.twig', [
            'projectId' => $this->keysProvider->getProjectId($this->domain->getLocale()),
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function embedAllPagesEcomailScriptAction(): Response
    {
        return $this->render('Front/Inline/MeasuringScript/ecomail.html.twig', [
            'user' => $this->getUser(),
        ]);
    }
}
