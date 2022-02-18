<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Shopsys\FrameworkBundle\Controller\Admin\CurrencyController as BaseCurrencyController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CurrencyController extends BaseCurrencyController
{
    /**
     * @Route("/currency/get-currency-symbol/", methods={"post"}, condition="request.isXmlHttpRequest()")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function currencySymbolAction(Request $request): JsonResponse
    {
        $domainId = $request->get('domainId');
        $currencySymbol = $this->currencyFacade->getDomainDefaultCurrencyByDomainId($domainId)->getCode();

        $responseData = ['success' => true, 'currencySymbol' => $currencySymbol];

        return new JsonResponse($responseData);
    }
}
