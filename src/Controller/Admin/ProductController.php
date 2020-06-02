<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Component\Cron\CronModuleFacade;
use App\Model\Product\MassEdit\MassEditFacade;
use App\Model\Product\ProductFacade;
use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Grid\Grid;
use Shopsys\FrameworkBundle\Component\Grid\GridFactory;
use Shopsys\FrameworkBundle\Component\Setting\Setting;
use Shopsys\FrameworkBundle\Controller\Admin\ProductController as BaseProductController;
use Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormData;
use Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormType;
use Shopsys\FrameworkBundle\Model\Administrator\AdministratorGridFacade;
use Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider;
use Shopsys\FrameworkBundle\Model\AdvancedSearch\AdvancedSearchProductFacade;
use Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityFacade;
use Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportCronModule;
use Shopsys\FrameworkBundle\Model\Product\Listing\ProductListAdminFacade;
use Shopsys\FrameworkBundle\Model\Product\MassAction\ProductMassActionFacade;
use Shopsys\FrameworkBundle\Model\Product\ProductDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\ProductVariantFacade;
use Shopsys\FrameworkBundle\Model\Product\Unit\UnitFacade;
use Shopsys\FrameworkBundle\Twig\ProductExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @property \App\Model\Product\ProductDataFactory $productDataFactory
 * @property \App\Model\Product\ProductVariantFacade $productVariantFacade
 * @property \App\Component\Setting\Setting $setting
 * @property \App\Model\Product\Availability\AvailabilityFacade $availabilityFacade
 */
class ProductController extends BaseProductController
{
    /**
     * @var \App\Model\Product\MassEdit\MassEditFacade
     */
    private $massEditFacade;

    /**
     * @var \App\Model\Product\ProductFacade
     */
    protected $productFacade;

    /**
     * @var \App\Component\Cron\CronModuleFacade
     */
    private $cronModuleFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\MassAction\ProductMassActionFacade $productMassActionFacade
     * @param \Shopsys\FrameworkBundle\Component\Grid\GridFactory $gridFactory
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Product\ProductDataFactory $productDataFactory
     * @param \Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider $breadcrumbOverrider
     * @param \Shopsys\FrameworkBundle\Model\Administrator\AdministratorGridFacade $administratorGridFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Listing\ProductListAdminFacade $productListAdminFacade
     * @param \Shopsys\FrameworkBundle\Model\AdvancedSearch\AdvancedSearchProductFacade $advancedSearchProductFacade
     * @param \App\Model\Product\ProductVariantFacade $productVariantFacade
     * @param \Shopsys\FrameworkBundle\Twig\ProductExtension $productExtension
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Product\Unit\UnitFacade $unitFacade
     * @param \App\Component\Setting\Setting $setting
     * @param \App\Model\Product\Availability\AvailabilityFacade $availabilityFacade
     * @param \App\Model\Product\MassEdit\MassEditFacade $massEditFacade
     * @param \App\Component\Cron\CronModuleFacade $cronModuleFacade
     */
    public function __construct(
        ProductMassActionFacade $productMassActionFacade,
        GridFactory $gridFactory,
        ProductFacade $productFacade,
        ProductDataFactoryInterface $productDataFactory,
        BreadcrumbOverrider $breadcrumbOverrider,
        AdministratorGridFacade $administratorGridFacade,
        ProductListAdminFacade $productListAdminFacade,
        AdvancedSearchProductFacade $advancedSearchProductFacade,
        ProductVariantFacade $productVariantFacade,
        ProductExtension $productExtension,
        Domain $domain,
        UnitFacade $unitFacade,
        Setting $setting,
        AvailabilityFacade $availabilityFacade,
        MassEditFacade $massEditFacade,
        CronModuleFacade $cronModuleFacade
    ) {
        parent::__construct($productMassActionFacade, $gridFactory, $productFacade, $productDataFactory, $breadcrumbOverrider, $administratorGridFacade, $productListAdminFacade, $advancedSearchProductFacade, $productVariantFacade, $productExtension, $domain, $unitFacade, $setting, $availabilityFacade);

        $this->massEditFacade = $massEditFacade;
        $this->cronModuleFacade = $cronModuleFacade;
    }

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

        $massEditForm = $this->massEditFacade->createMassEditFormFromRequest($request);

        $isAdvancedSearchFormSubmitted = $this->advancedSearchProductFacade->isAdvancedSearchFormSubmitted($request);
        if ($isAdvancedSearchFormSubmitted) {
            $queryBuilder = $this->advancedSearchProductFacade->getQueryBuilderByAdvancedSearchData($advancedSearchData);
        } else {
            $queryBuilder = $this->productListAdminFacade->getQueryBuilderByQuickSearchData($quickSearchData);
        }

        $grid = $this->getGrid($queryBuilder);

        /** @var \Symfony\Component\Form\SubmitButton $submitButton */
        $submitButton = $massEditForm->get('submit');
        /** @var \Symfony\Component\Form\SubmitButton $submitAndExportButton */
        $submitAndExportButton = $massEditForm->get('submitAndExport');
        if ($submitButton->isClicked() || $submitAndExportButton->isClicked()) {
            $this->performMassEdit(
                array_map('intval', $grid->getSelectedRowIds()),
                $massEditForm->getData(),
                $queryBuilder
            );

            if ($submitAndExportButton->isClicked()) {
                $this->cronModuleFacade->scheduleModuleByServiceId(ProductExportCronModule::class);
                $this->addInfoFlash(
                    t('Byl naplánován export produktů do Elasticsearch, který bude proveden do 5-ti minut')
                );
            }

            return $this->redirect($request->headers->get('referer', $this->generateUrl('admin_product_list')));
        }

        $this->administratorGridFacade->restoreAndRememberGridLimit($administrator, $grid);

        $productCanBeCreated = $this->productCanBeCreated();

        return $this->render('@ShopsysFramework/Admin/Content/Product/list.html.twig', [
            'gridView' => $grid->createView(),
            'quickSearchForm' => $quickSearchForm->createView(),
            'advancedSearchForm' => $advancedSearchForm->createView(),
            'massEditForm' => $massEditForm->createView(),
            'isAdvancedSearchFormSubmitted' => $isAdvancedSearchFormSubmitted,
            'productCanBeCreated' => $productCanBeCreated,
        ]);
    }

    /**
     * @param int[] $checkedProductsIds
     * @param mixed[] $formData
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     */
    private function performMassEdit(array $checkedProductsIds, array $formData, QueryBuilder $queryBuilder): void
    {
        $countOfProductsToEdit = $this->massEditFacade->getCountOfSelectedProducts($formData, $queryBuilder, $checkedProductsIds);

        if ($countOfProductsToEdit > MassEditFacade::MASS_EDIT_MAX_LIMIT) {
            $this->addInfoFlash(
                t(
                    'Maximální počet produktů, které můžete upravit pomocí hromadných operací, je {{maxProductCount}}. Máte vybráno {{selectedProductCount}} produktů',
                    [
                        '{{maxProductCount}}' => MassEditFacade::MASS_EDIT_MAX_LIMIT,
                        '{{selectedProductCount}}' => $countOfProductsToEdit,
                    ]
                )
            );

            return;
        }

        $this->massEditFacade->performMassEdit($formData, $queryBuilder, $checkedProductsIds);

        $this->addSuccessFlash(t('Bulk editing done'));
    }

    /**
     * @Route("/product/get-mass-edit-form/", methods={"post"})
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getMassEditFormAction(Request $request): Response
    {
        $massEditForm = $this->massEditFacade->createMassEditForm(
            $request->get('selectedSubjectName'),
            $request->get('selectedOperationName')
        );

        return $this->render('Admin/Content/Product/MassEdit/massEditFormPartial.html.twig', [
            'massEditForm' => $massEditForm->createView(),
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
