<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Component\Grid\Grid;
use Shopsys\FrameworkBundle\Controller\Admin\ProductController as BaseProductController;
use Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormData;
use Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @property \App\Model\Product\ProductDataFactory $productDataFactory
 * @property \App\Model\Product\ProductVariantFacade $productVariantFacade
 * @property \App\Component\Setting\Setting $setting
 * @property \App\Model\Product\Availability\AvailabilityFacade $availabilityFacade
 * @property \App\Model\Product\ProductFacade $productFacade
 * @method __construct(\Shopsys\FrameworkBundle\Model\Product\MassAction\ProductMassActionFacade $productMassActionFacade, \Shopsys\FrameworkBundle\Component\Grid\GridFactory $gridFactory, \App\Model\Product\ProductFacade $productFacade, \App\Model\Product\ProductDataFactory $productDataFactory, \Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider $breadcrumbOverrider, \Shopsys\FrameworkBundle\Model\Administrator\AdministratorGridFacade $administratorGridFacade, \Shopsys\FrameworkBundle\Model\Product\Listing\ProductListAdminFacade $productListAdminFacade, \Shopsys\FrameworkBundle\Model\AdvancedSearch\AdvancedSearchProductFacade $advancedSearchProductFacade, \App\Model\Product\ProductVariantFacade $productVariantFacade, \Shopsys\FrameworkBundle\Twig\ProductExtension $productExtension, \Shopsys\FrameworkBundle\Component\Domain\Domain $domain, \Shopsys\FrameworkBundle\Model\Product\Unit\UnitFacade $unitFacade, \App\Component\Setting\Setting $setting, \App\Model\Product\Availability\AvailabilityFacade $availabilityFacade)
 */
class ProductController extends BaseProductController
{
    /**
     * @Route("/product/list/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request): Response
    {
        $administrator = $this->getUser();
        /* @var $administrator \App\Model\Administrator\Administrator */

        $advancedSearchForm = $this->advancedSearchProductFacade->createAdvancedSearchForm($request);
        $advancedSearchData = $advancedSearchForm->getData();
        $quickSearchData = new QuickSearchFormData();

        $quickSearchForm = $this->createForm(QuickSearchFormType::class, $quickSearchData);

        // Cannot call $form->handleRequest() because the GET forms are not handled in POST request.
        // See: https://github.com/symfony/symfony/issues/12244
        $quickSearchForm->submit($request->query->get($quickSearchForm->getName()));

        $isAdvancedSearchFormSubmitted = $this->advancedSearchProductFacade->isAdvancedSearchFormSubmitted($request);
        if ($isAdvancedSearchFormSubmitted) {
            $queryBuilder = $this->advancedSearchProductFacade->getQueryBuilderByAdvancedSearchData($advancedSearchData);
        } else {
            $queryBuilder = $this->productListAdminFacade->getQueryBuilderByQuickSearchData($quickSearchData);
        }

        $grid = $this->getGrid($queryBuilder);
        $this->administratorGridFacade->restoreAndRememberGridLimit($administrator, $grid);

        $productCanBeCreated = $this->productCanBeCreated();

        return $this->render('@ShopsysFramework/Admin/Content/Product/list.html.twig', [
            'gridView' => $grid->createView(),
            'quickSearchForm' => $quickSearchForm->createView(),
            'advancedSearchForm' => $advancedSearchForm->createView(),
            'isAdvancedSearchFormSubmitted' => $isAdvancedSearchFormSubmitted,
            'productCanBeCreated' => $productCanBeCreated,
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function getGrid(QueryBuilder $queryBuilder): Grid
    {
        $grid = parent::getGrid($queryBuilder);

        $grid->addColumn('catnum', 'p.catnum', t('SKU'), true);

        return $grid;
    }

    /**
     * @deprecated since US-7741, variants are paired using variantId
     * @see \App\Model\Product\ProductVariantTropicFacade, method refreshVariantStatus
     *
     * @Route("/product/create-variant/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function createVariantAction(Request $request)
    {
        throw new NotFoundHttpException('Deprecated, you should use Product::variantId to pair variants, see ProductVariantTropicFacade::refreshVariantStatus');
    }
}
