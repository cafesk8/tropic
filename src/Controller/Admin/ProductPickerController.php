<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Shopsys\FrameworkBundle\Controller\Admin\ProductPickerController as BaseProductPickerController;
use Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormData;
use Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @property \App\Model\Product\ProductFacade $productFacade
 * @method __construct(\Shopsys\FrameworkBundle\Model\Administrator\AdministratorGridFacade $administratorGridFacade, \Shopsys\FrameworkBundle\Component\Grid\GridFactory $gridFactory, \Shopsys\FrameworkBundle\Model\Product\Listing\ProductListAdminFacade $productListAdminFacade, \Shopsys\FrameworkBundle\Model\AdvancedSearch\AdvancedSearchProductFacade $advancedSearchProductFacade, \App\Model\Product\ProductFacade $productFacade)
 */
class ProductPickerController extends BaseProductPickerController
{
    private const MASS_EDIT_MAX_LIMIT = 1000;

    /**
     * @Route("/product-picker/pick-multiple/{jsInstanceId}/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $jsInstanceId
     * @param bool $allowMainVariants
     * @param bool $allowVariants
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function pickMultipleAction(
        Request $request,
        $jsInstanceId,
        bool $allowMainVariants = true,
        bool $allowVariants = true
    ): Response
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
            ]
        );
    }

    /**
     * @Route("/product-picker/pick-all/")
     * @param \Symfony\Component\HttpFoundation\Request $request
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

        $countOfFoundProducts = $queryBuilder->select('COUNT(p)')->getQuery()->getSingleScalarResult();
        if ($countOfFoundProducts > self::MASS_EDIT_MAX_LIMIT) {
            $content = json_encode([
                'errorMessage' => t('Maximální počet produktů pro hromadnou operaci je %max%. Počet nalezených produktů je %found%. Vyhledejte menší počet produktů a hromadnou operaci opakujte.', [
                    '%max%' => self::MASS_EDIT_MAX_LIMIT,
                    '%found%' => $countOfFoundProducts,
                ]),
            ]);
        } else {
            $queryBuilder->select('p.id, pt.name');
            $content = json_encode(['products' => $queryBuilder->getQuery()->getResult()]);
        }

        return new Response($content);
    }
}
