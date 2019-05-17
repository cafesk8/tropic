<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Admin;

use Shopsys\FrameworkBundle\Controller\Admin\ProductPickerController as BaseProductPickerController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductPickerController extends BaseProductPickerController
{
    /**
     * @Route("/product-picker/pick-multiple/{jsInstanceId}/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $jsInstanceId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function pickMultipleAction(Request $request, $jsInstanceId): Response
    {
        return $this->getPickerResponse(
            $request,
            [
                'isMultiple' => true,
            ],
            [
                'isMultiple' => true,
                'jsInstanceId' => $jsInstanceId,
                'allowMainVariants' => $request->query->getBoolean('allowMainVariants', true),
                'allowVariants' => $request->query->getBoolean('allowVariants', true),
                'isMainVariantGroup' => $request->query->getBoolean('isMainVariantGroup', false),
            ]
        );
    }

    /**
     * @Route("/product-picker/pick-single/{parentInstanceId}/", defaults={"parentInstanceId"="__instance_id__"})
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $parentInstanceId
     */
    public function pickSingleAction(Request $request, $parentInstanceId)
    {
        return $this->getPickerResponse(
            $request,
            [
                'isMultiple' => false,
            ],
            [
                'isMultiple' => false,
                'parentInstanceId' => $parentInstanceId,
                'allowMainVariants' => $request->query->getBoolean('allowMainVariants', true),
                'allowVariants' => $request->query->getBoolean('allowVariants', true),
                'isMainVariantGroup' => $request->query->getBoolean('isMainVariantGroup', false),
            ]
        );
    }
}
