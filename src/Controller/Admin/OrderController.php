<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Form\Admin\OrderMassActionFormType;
use App\Model\Order\Mall\Exception\StatusChangException;
use App\Model\Order\MassAction\CsvExportMassAction;
use App\Model\Order\MassAction\OrderMassActionData;
use App\Model\Order\MassAction\OrderMassActionFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Grid\DataSourceInterface;
use Shopsys\FrameworkBundle\Component\Grid\GridFactory;
use Shopsys\FrameworkBundle\Component\Grid\QueryBuilderWithRowManipulatorDataSource;
use Shopsys\FrameworkBundle\Controller\Admin\OrderController as BaseOrderController;
use Shopsys\FrameworkBundle\Form\Admin\Order\OrderFormType;
use Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormData;
use Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormType;
use Shopsys\FrameworkBundle\Model\Administrator\AdministratorGridFacade;
use Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider;
use Shopsys\FrameworkBundle\Model\AdvancedSearchOrder\AdvancedSearchOrderFacade;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItemFacade;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItemPriceCalculation;
use Shopsys\FrameworkBundle\Model\Order\OrderDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Order\OrderFacade;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @property \App\Model\Order\OrderFacade $orderFacade
 * @property \App\Model\Order\Item\OrderItemFacade $orderItemFacade
 * @property \App\Model\Order\OrderDataFactory $orderDataFactory
 */
class OrderController extends BaseOrderController
{
    /**
     * @var \App\Model\Order\MassAction\OrderMassActionFacade
     */
    private $orderMassActionFacade;

    /**
     * @var \App\Model\Order\MassAction\CsvExportMassAction
     */
    private $csvExportMassAction;

    /**
     * @param \App\Model\Order\OrderFacade $orderFacade
     * @param \Shopsys\FrameworkBundle\Model\AdvancedSearchOrder\AdvancedSearchOrderFacade $advancedSearchOrderFacade
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\OrderItemPriceCalculation $orderItemPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Administrator\AdministratorGridFacade $administratorGridFacade
     * @param \Shopsys\FrameworkBundle\Component\Grid\GridFactory $gridFactory
     * @param \Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider $breadcrumbOverrider
     * @param \App\Model\Order\Item\OrderItemFacade $orderItemFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Order\OrderDataFactory $orderDataFactory
     * @param \App\Model\Order\MassAction\OrderMassActionFacade $orderMassActionFacade
     * @param \App\Model\Order\MassAction\CsvExportMassAction $csvExportMassAction
     */
    public function __construct(
        OrderFacade $orderFacade,
        AdvancedSearchOrderFacade $advancedSearchOrderFacade,
        OrderItemPriceCalculation $orderItemPriceCalculation,
        AdministratorGridFacade $administratorGridFacade,
        GridFactory $gridFactory,
        BreadcrumbOverrider $breadcrumbOverrider,
        OrderItemFacade $orderItemFacade,
        Domain $domain,
        OrderDataFactoryInterface $orderDataFactory,
        OrderMassActionFacade $orderMassActionFacade,
        CsvExportMassAction $csvExportMassAction
    ) {
        parent::__construct(
            $orderFacade,
            $advancedSearchOrderFacade,
            $orderItemPriceCalculation,
            $administratorGridFacade,
            $gridFactory,
            $breadcrumbOverrider,
            $orderItemFacade,
            $domain,
            $orderDataFactory
        );

        $this->orderMassActionFacade = $orderMassActionFacade;
        $this->csvExportMassAction = $csvExportMassAction;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $id)
    {
        /** @var \App\Model\Order\Order $order */
        $order = $this->orderFacade->getById($id);

        /** @var \App\Model\Order\OrderData $orderData */
        $orderData = $this->orderDataFactory->createFromOrder($order);

        $form = $this->createForm(OrderFormType::class, $orderData, ['order' => $order]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $order = $this->orderFacade->edit($id, $orderData);

                $this->addSuccessFlashTwig(
                    t('Order Nr. <strong><a href="{{ url }}">{{ number }}</a></strong> modified'),
                    [
                        'number' => $order->getNumber(),
                        'url' => $this->generateUrl('admin_order_edit', ['id' => $order->getId()]),
                    ]
                );
                return $this->redirectToRoute('admin_order_list');
            } catch (\Shopsys\FrameworkBundle\Model\Customer\Exception\CustomerUserNotFoundException $e) {
                $this->addErrorFlash(
                    t('Entered customer not found, please check entered data.')
                );
            } catch (\Shopsys\FrameworkBundle\Model\Mail\Exception\MailException $e) {
                $this->addErrorFlash(t('Unable to send updating e-mail'));
            } catch (StatusChangException $statusChangException) {
                $this->addErrorFlash(
                    t('Nepoda??ilo se zm??nit stav objedn??vky na Mall.cz (%errorMessage%).', [
                        '%errorMessage%' => $statusChangException->getMessage(),
                    ])
                );
                $orderData->mallStatus = $order->getMallStatus();
                $form = $this->createForm(OrderFormType::class, $orderData, ['order' => $order]);
            }
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addErrorFlash(t('Please check the correctness of all data filled.'));
        }

        $this->breadcrumbOverrider->overrideLastItem(t('Editing order - Nr. %number%', ['%number%' => $order->getNumber()]));

        return $this->render('@ShopsysFramework/Admin/Content/Order/edit.html.twig', [
            'form' => $form->createView(),
            'order' => $order,
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request): Response
    {
        /* @var $administrator \App\Model\Administrator\Administrator */
        $administrator = $this->getUser();

        $advancedSearchForm = $this->advancedSearchOrderFacade->createAdvancedSearchOrderForm($request);
        $advancedSearchData = $advancedSearchForm->getData();

        $quickSearchForm = $this->createForm(QuickSearchFormType::class, new QuickSearchFormData());
        $quickSearchForm->handleRequest($request);

        $isAdvancedSearchFormSubmitted = $this->advancedSearchOrderFacade->isAdvancedSearchOrderFormSubmitted($request);
        if ($isAdvancedSearchFormSubmitted) {
            $queryBuilder = $this->advancedSearchOrderFacade->getQueryBuilderByAdvancedSearchOrderData($advancedSearchData);
        } else {
            $queryBuilder = $this->orderFacade->getOrderListQueryBuilderByQuickSearchData($quickSearchForm->getData());
        }

        $dataSource = new QueryBuilderWithRowManipulatorDataSource(
            $queryBuilder,
            'o.id',
            function ($row) {
                return $this->addOrderEntityToDataSource($row);
            }
        );

        $grid = $this->gridFactory->create('orderList', $dataSource);
        $grid->enablePaging();
        $grid->enableSelecting();
        $grid->setDefaultOrder('created_at', DataSourceInterface::ORDER_DESC);

        $grid->addColumn('preview', 'o.id', t('Preview'), false);
        $grid->addColumn('number', 'o.number', t('Order Nr.'), true);
        $grid->addColumn('created_at', 'o.createdAt', t('Created'), true);
        $grid->addColumn('customer_name', 'customerName', t('Customer'), true);
        if ($this->domain->isMultidomain()) {
            $grid->addColumn('domain_id', 'o.domainId', t('Domain'), true);
        }
        $grid->addColumn('status_name', 'statusName', t('Status'), true);
        $grid->addColumn('gopay_status', 'o.goPayStatus', t('Stav GoPay'), true);
        $grid->addColumn('total_price', 'o.totalPriceWithVat', t('Total price'), false)
            ->setClassAttribute('text-right text-no-wrap');

        $grid->setActionColumnClassAttribute('table-col table-col-10');
        $grid->addEditActionColumn('admin_order_edit', ['id' => 'id']);
        $grid->addDeleteActionColumn('admin_order_delete', ['id' => 'id'])
            ->setConfirmMessage(t('Do you really want to remove the order?'));

        $grid->setTheme('@ShopsysFramework/Admin/Content/Order/listGrid.html.twig');

        $this->administratorGridFacade->restoreAndRememberGridLimit($administrator, $grid);

        $massActionForm = $this->createForm(OrderMassActionFormType::class);
        $massActionForm->handleRequest($request);

        /** @var \Symfony\Component\Form\SubmitButton $submitButton */
        $submitButton = $massActionForm->get('submit');
        if ($submitButton->isClicked()) {
            $selectedOrdersIds = $this->orderMassActionFacade->getOrdersIdsForMassAction(
                $massActionForm->getData(),
                $queryBuilder,
                array_map('intval', $grid->getSelectedRowIds())
            );

            if ($massActionForm->getData()->action === OrderMassActionData::ACTION_CSV_EXPORT) {
                return $this->getResponseForCsvExportByOrdersIds($selectedOrdersIds);
            }

            return $this->redirect($request->headers->get('referer', $this->generateUrl('admin_order_list')));
        }

        return $this->render('@ShopsysFramework/Admin/Content/Order/list.html.twig', [
            'gridView' => $grid->createView(),
            'quickSearchForm' => $quickSearchForm->createView(),
            'advancedSearchForm' => $advancedSearchForm->createView(),
            'isAdvancedSearchFormSubmitted' => $this->advancedSearchOrderFacade->isAdvancedSearchOrderFormSubmitted($request),
            'massActionForm' => $massActionForm->createView(),
        ]);
    }

    /**
     * @param int[] $ordersIds
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function getResponseForCsvExportByOrdersIds(array $ordersIds): Response
    {
        $ordersCsvExport = $this->csvExportMassAction->process($ordersIds);
        $response = new Response($ordersCsvExport);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="export-objednavek.csv"');

        $this->addSuccessFlash(t('Bulk editing done'));

        return $response;
    }
}
