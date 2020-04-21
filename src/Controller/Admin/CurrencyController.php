<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Pricing\Currency\CurrencyFacade;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CurrencyController
{
    /**
     * @var \App\Model\Pricing\Currency\CurrencyFacade
     */
    private $currencyFacade;

    /**
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     */
    public function __construct(CurrencyFacade $currencyFacade)
    {
        $this->currencyFacade = $currencyFacade;
    }

    /**
     * @Route("/currency/get-currency-symbol/", methods={"post"}, condition="request.isXmlHttpRequest()")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function currencySymbolAction(Request $request)
    {
        $domainId = $request->get('domainId');
        $currencySymbol = $this->currencyFacade->getDomainDefaultCurrencyByDomainId($domainId)->getCode();

        $responseData = ['success' => true, 'currencySymbol' => $currencySymbol];

        return new JsonResponse($responseData);
    }
}
