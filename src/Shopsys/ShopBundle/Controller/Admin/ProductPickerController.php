<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Admin;

use Shopsys\FrameworkBundle\Controller\Admin\ProductPickerController as BaseProductPickerController;
use Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormData;
use Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormType;
use Shopsys\ShopBundle\Model\Product\Product;
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
                'jsInstanceId' => $jsInstanceId,
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

    /**
     * @Route("/product-picker/pick-all/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $jsInstanceId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function pickAllAction(Request $request): Response
    {
        $advancedSearchForm = $this->advancedSearchProductFacade->createAdvancedSearchForm($request);
        $advancedSearchData = $advancedSearchForm->getData();
        $quickSearchData = new QuickSearchFormData();

        $quickSearchForm = $this->createForm(QuickSearchFormType::class, $quickSearchData);
        $quickSearchForm->handleRequest($request);

        $isAdvancedSearchFormSubmitted = $this->advancedSearchProductFacade->isAdvancedSearchFormSubmitted($request);
        if ($isAdvancedSearchFormSubmitted) {
            $queryBuilder = $this->advancedSearchProductFacade->getQueryBuilderByAdvancedSearchData($advancedSearchData);
        } else {
            $queryBuilder = $this->productListAdminFacade->getQueryBuilderByQuickSearchData($quickSearchData);
        }

        $queryBuilder->select('p.id, pt.name');

        $content = json_encode($queryBuilder->getQuery()->getResult());
        return new Response($content);
    }
}
