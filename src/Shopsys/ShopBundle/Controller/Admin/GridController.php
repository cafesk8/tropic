<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Admin;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Shopsys\FrameworkBundle\Controller\Admin\GridController as BaseGridController;
use Shopsys\ShopBundle\Model\Product\Parameter\Exception\ParameterUsedAsDistinguishingParameterException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class GridController extends BaseGridController
{
    /**
     * @Route("/_grid/save-form/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function saveFormAction(Request $request)
    {
        try {
            return parent::saveFormAction($request);
        } catch (ParameterUsedAsDistinguishingParameterException $exception) {
            $responseData['success'] = false;
            $responseData['errors'] = [t('Parametru nelze upravit viditelnost, protože se používá jako rozlišující parametr')];

            return new JsonResponse($responseData);
        }
    }
}
