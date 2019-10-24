<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Admin;

use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Grid\Grid;
use Shopsys\FrameworkBundle\Component\Grid\GridFactory;
use Shopsys\FrameworkBundle\Component\Setting\Setting;
use Shopsys\FrameworkBundle\Controller\Admin\ProductController as BaseProductController;
use Shopsys\FrameworkBundle\Form\Admin\Product\ProductFormType;
use Shopsys\FrameworkBundle\Form\Admin\Product\VariantFormType;
use Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormData;
use Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormType;
use Shopsys\FrameworkBundle\Model\Administrator\AdministratorGridFacade;
use Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider;
use Shopsys\FrameworkBundle\Model\AdvancedSearch\AdvancedSearchProductFacade;
use Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityFacade;
use Shopsys\FrameworkBundle\Model\Product\Listing\ProductListAdminFacade;
use Shopsys\FrameworkBundle\Model\Product\MassAction\ProductMassActionFacade;
use Shopsys\FrameworkBundle\Model\Product\ProductDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\ProductVariantFacade;
use Shopsys\FrameworkBundle\Model\Product\Unit\UnitFacade;
use Shopsys\FrameworkBundle\Twig\ProductExtension;
use Shopsys\ShopBundle\Form\Admin\VariantFormTypeExtension;
use Shopsys\ShopBundle\Model\Product\MassEdit\MassEditFacade;
use Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade;
use Shopsys\ShopBundle\Model\Product\ProductFacade;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends BaseProductController
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\MassEdit\MassEditFacade
     */
    private $massEditFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    protected $productFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade
     */
    private $parameterFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\MassAction\ProductMassActionFacade $productMassActionFacade
     * @param \Shopsys\FrameworkBundle\Component\Grid\GridFactory $gridFactory
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductDataFactoryInterface $productDataFactory
     * @param \Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider $breadcrumbOverrider
     * @param \Shopsys\FrameworkBundle\Model\Administrator\AdministratorGridFacade $administratorGridFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Listing\ProductListAdminFacade $productListAdminFacade
     * @param \Shopsys\FrameworkBundle\Model\AdvancedSearch\AdvancedSearchProductFacade $advancedSearchProductFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductVariantFacade $productVariantFacade
     * @param \Shopsys\FrameworkBundle\Twig\ProductExtension $productExtension
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Product\Unit\UnitFacade $unitFacade
     * @param \Shopsys\FrameworkBundle\Component\Setting\Setting $setting
     * @param \Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityFacade $availabilityFacade
     * @param \Shopsys\ShopBundle\Model\Product\MassEdit\MassEditFacade $massEditFacade
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade $parameterFacade
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
        ParameterFacade $parameterFacade
    ) {
        parent::__construct($productMassActionFacade, $gridFactory, $productFacade, $productDataFactory, $breadcrumbOverrider, $administratorGridFacade, $productListAdminFacade, $advancedSearchProductFacade, $productVariantFacade, $productExtension, $domain, $unitFacade, $setting, $availabilityFacade);

        $this->massEditFacade = $massEditFacade;
        $this->parameterFacade = $parameterFacade;
    }

    /**
     * @Route("/product/create-variant/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createVariantAction(Request $request): Response
    {
        $form = $this->createForm(VariantFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            /** @var \Shopsys\ShopBundle\Model\Product\Product $mainVariant */
            $mainVariant = $formData[VariantFormType::MAIN_VARIANT];
            $mainVariant->setDistinguishingParameter($formData[VariantFormTypeExtension::DISTINGUISHING_PARAMETER]);
            try {
                $newMainVariant = $this->productVariantFacade->createVariant($mainVariant, $formData[VariantFormType::VARIANTS]);

                $this->getFlashMessageSender()->addSuccessFlashTwig(
                    t('Variant <strong>{{ productVariant|productDisplayName }}</strong> successfully created.'),
                    [
                        'productVariant' => $newMainVariant,
                    ]
                );

                return $this->redirectToRoute('admin_product_edit', ['id' => $newMainVariant->getId()]);
            } catch (\Shopsys\FrameworkBundle\Model\Product\Exception\VariantException $ex) {
                $this->getFlashMessageSender()->addErrorFlash(
                    t('Not possible to create variations of products that are already variant or main variant.')
                );
            }
        }

        return $this->render('@ShopsysFramework/Admin/Content/Product/createVariant.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/product/list/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request): Response
    {
        $administrator = $this->getUser();
        /* @var $administrator \Shopsys\FrameworkBundle\Model\Administrator\Administrator */

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

        if ($massEditForm->get('submit')->isClicked()) {
            $this->performMassEdit(
                array_map('intval', $grid->getSelectedRowIds()),
                $massEditForm->getData(),
                $queryBuilder
            );

            return $this->redirect($request->headers->get('referer', $this->generateUrl('admin_product_list')));
        }

        $this->administratorGridFacade->restoreAndRememberGridLimit($administrator, $grid);

        $productCanBeCreated = $this->productCanBeCreated();

        return $this->render('@ShopsysFramework/Admin/Content/Product/list.html.twig', [
            'gridView' => $grid->createView(),
            'quickSearchForm' => $quickSearchForm->createView(),
            'advancedSearchForm' => $advancedSearchForm->createView(),
            'massEditForm' => $massEditForm->createView(),
            'isAdvancedSearchFormSubmitted' => $this->advancedSearchProductFacade->isAdvancedSearchFormSubmitted($request),
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
            $this->getFlashMessageSender()->addInfoFlash(
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

        $this->getFlashMessageSender()->addSuccessFlash(t('Bulk editing done'));
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

        return $this->render('@ShopsysShop/Admin/Content/Product/MassEdit/massEditFormPartial.html.twig', [
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
        $grid->addColumn('finished', 'p.finished', t('Produkt je hotový'), true);

        return $grid;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param mixed $id
     */
    public function editAction(Request $request, $id)
    {
        $product = $this->productFacade->getById($id);
        $productData = $this->productDataFactory->createFromProduct($product);

        $form = $this->createForm(ProductFormType::class, $productData, ['product' => $product]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->productFacade->edit($id, $form->getData());
            $this->productFacade->fillVariantNamesFromMainVariantNames($product, $this->parameterFacade);

            $this->getFlashMessageSender()->addSuccessFlashTwig(
                t('Product <strong>{{ product|productDisplayName }}</strong> modified'),
                [
                    'product' => $product,
                ]
            );

            return $this->redirectToRoute('admin_product_edit', ['id' => $product->getId()]);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->getFlashMessageSender()->addErrorFlashTwig(t('Please check the correctness of all data filled.'));
        }

        $this->breadcrumbOverrider->overrideLastItem(t('Editing product - %name%', ['%name%' => $this->productExtension->getProductDisplayName($product)]));

        $viewParameters = [
            'form' => $form->createView(),
            'product' => $product,
            'domains' => $this->domain->getAll(),
        ];

        return $this->render('@ShopsysFramework/Admin/Content/Product/edit.html.twig', $viewParameters);
    }
}
