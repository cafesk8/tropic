<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Admin;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Component\EntityExtension\QueryBuilder;
use Shopsys\FrameworkBundle\Component\Grid\Grid;
use Shopsys\FrameworkBundle\Component\Grid\GridFactory;
use Shopsys\FrameworkBundle\Component\Grid\MoneyConvertingDataSourceDecorator;
use Shopsys\FrameworkBundle\Component\Grid\QueryBuilderDataSource;
use Shopsys\FrameworkBundle\Component\Router\DomainRouterFactory;
use Shopsys\FrameworkBundle\Controller\Admin\CustomerController as BaseCustomerController;
use Shopsys\FrameworkBundle\Form\Admin\Customer\CustomerFormType;
use Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormData;
use Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormType;
use Shopsys\FrameworkBundle\Model\Administrator\AdministratorGridFacade;
use Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider;
use Shopsys\FrameworkBundle\Model\Customer\CustomerDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Customer\CustomerFacade;
use Shopsys\FrameworkBundle\Model\Customer\CustomerListAdminFacade;
use Shopsys\FrameworkBundle\Model\Customer\UserDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Order\OrderFacade;
use Shopsys\FrameworkBundle\Model\Security\LoginAsUserFacade;
use Shopsys\FrameworkBundle\Twig\DateTimeFormatterExtension;
use Shopsys\ShopBundle\Model\BushmanClub\CurrentBushmanClubPointPeriods;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerController extends BaseCustomerController
{
    /**
     * @var \Shopsys\ShopBundle\Model\BushmanClub\CurrentBushmanClubPointPeriods
     */
    private $bushmanClubPointPeriodSettings;

    /**
     * @var \Shopsys\FrameworkBundle\Twig\DateTimeFormatterExtension
     */
    private $dateTimeFormatterExtension;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\UserDataFactoryInterface $userDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\CustomerListAdminFacade $customerListAdminFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\CustomerFacade $customerFacade
     * @param \Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider $breadcrumbOverrider
     * @param \Shopsys\FrameworkBundle\Model\Administrator\AdministratorGridFacade $administratorGridFacade
     * @param \Shopsys\FrameworkBundle\Component\Grid\GridFactory $gridFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderFacade $orderFacade
     * @param \Shopsys\FrameworkBundle\Model\Security\LoginAsUserFacade $loginAsUserFacade
     * @param \Shopsys\FrameworkBundle\Component\Router\DomainRouterFactory $domainRouterFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\CustomerDataFactoryInterface $customerDataFactory
     * @param \Shopsys\ShopBundle\Model\BushmanClub\CurrentBushmanClubPointPeriods $bushmanClubPointPeriodSettings
     * @param \Shopsys\FrameworkBundle\Twig\DateTimeFormatterExtension $dateTimeFormatterExtension
     */
    public function __construct(
        UserDataFactoryInterface $userDataFactory,
        CustomerListAdminFacade $customerListAdminFacade,
        CustomerFacade $customerFacade,
        BreadcrumbOverrider $breadcrumbOverrider,
        AdministratorGridFacade $administratorGridFacade,
        GridFactory $gridFactory,
        AdminDomainTabsFacade $adminDomainTabsFacade,
        OrderFacade $orderFacade,
        LoginAsUserFacade $loginAsUserFacade,
        DomainRouterFactory $domainRouterFactory,
        CustomerDataFactoryInterface $customerDataFactory,
        CurrentBushmanClubPointPeriods $bushmanClubPointPeriodSettings,
        DateTimeFormatterExtension $dateTimeFormatterExtension
    ) {
        parent::__construct(
            $userDataFactory,
            $customerListAdminFacade,
            $customerFacade,
            $breadcrumbOverrider,
            $administratorGridFacade,
            $gridFactory,
            $adminDomainTabsFacade,
            $orderFacade,
            $loginAsUserFacade,
            $domainRouterFactory,
            $customerDataFactory
        );

        $this->bushmanClubPointPeriodSettings = $bushmanClubPointPeriodSettings;
        $this->dateTimeFormatterExtension = $dateTimeFormatterExtension;
    }

    /**
     * @Route("/customer/edit/{id}", requirements={"id" = "\d+"})
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $id): Response
    {
        $user = $this->customerFacade->getUserById($id);
        $customerData = $this->customerDataFactory->createFromUser($user);

        $form = $this->createForm(CustomerFormType::class, $customerData, [
            'user' => $user,
            'domain_id' => $this->adminDomainTabsFacade->getSelectedDomainId(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->customerFacade->editByAdmin($id, $customerData);

            $this->getFlashMessageSender()->addSuccessFlashTwig(
                t('Customer <strong><a href="{{ url }}">{{ name }}</a></strong> modified'),
                [
                    'name' => $user->getFullName(),
                    'url' => $this->generateUrl('admin_customer_edit', ['id' => $user->getId()]),
                ]
            );

            return $this->redirectToRoute('admin_customer_list');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->getFlashMessageSender()->addErrorFlashTwig(t('Please check the correctness of all data filled.'));
        }

        $this->breadcrumbOverrider->overrideLastItem(t('Editing customer - %name%', ['%name%' => $user->getFullName()]));

        $orders = $this->orderFacade->getCustomerOrderList($user);

        return $this->render('@ShopsysFramework/Admin/Content/Customer/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'orders' => $orders,
            'ssoLoginAsUserUrl' => $this->getSsoLoginAsUserUrl($user),
            'bushmanClubPointPeriods' => $this->bushmanClubPointPeriodSettings->getPeriods(),
        ]);
    }

    /**
     * @Route("/customer/list/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request): Response
    {
        $administrator = $this->getUser();
        /* @var $administrator \Shopsys\FrameworkBundle\Model\Administrator\Administrator */

        $quickSearchForm = $this->createForm(QuickSearchFormType::class, new QuickSearchFormData());
        $quickSearchForm->handleRequest($request);

        $queryBuilder = $this->customerListAdminFacade->getCustomerListQueryBuilderByQuickSearchData(
            $this->adminDomainTabsFacade->getSelectedDomainId(),
            $quickSearchForm->getData()
        );

        $grid = $this->getCustomerGrid($queryBuilder);
        $this->administratorGridFacade->restoreAndRememberGridLimit($administrator, $grid);

        return $this->render('@ShopsysFramework/Admin/Content/Customer/list.html.twig', [
            'gridView' => $grid->createView(),
            'quickSearchForm' => $quickSearchForm->createView(),
        ]);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\QueryBuilder $queryBuilder
     * @return \Shopsys\FrameworkBundle\Component\Grid\Grid
     */
    private function getCustomerGrid(QueryBuilder $queryBuilder): Grid
    {
        $innerDataSource = new QueryBuilderDataSource($queryBuilder, 'u.id');
        $dataSource = new MoneyConvertingDataSourceDecorator($innerDataSource, ['ordersSumPrice']);

        $grid = $this->gridFactory->create('customerList', $dataSource);
        $grid->enablePaging();
        $grid->setDefaultOrder('name');

        $grid->addColumn('name', 'name', t('Full name'), true);
        $grid->addColumn('city', 'city', t('City'), true);
        $grid->addColumn('telephone', 'telephone', t('Telephone'), true);
        $grid->addColumn('email', 'u.email', t('E-mail'), true);
        $grid->addColumn('pricingGroup', 'pricingGroup', t('Pricing group'), true);
        $grid->addColumn('orders_count', 'ordersCount', t('Number of orders'), true)->setClassAttribute('text-right');
        $grid->addColumn('orders_sum_price', 'ordersSumPrice', t('Orders value'), true)
            ->setClassAttribute('text-right');
        $grid->addColumn('last_order_at', 'lastOrderAt', t('Last order'), true)
            ->setClassAttribute('text-right');

        foreach ($this->bushmanClubPointPeriodSettings->getPeriods() as $periodName => $bushmanClubPointPeriod) {
            $grid->addColumn(
                'bushman_club_point_' . $periodName,
                'id',
                $this->dateTimeFormatterExtension->formatDate($bushmanClubPointPeriod->getDateFrom()) . ' - ' . $this->dateTimeFormatterExtension->formatDate($bushmanClubPointPeriod->getDateTo()),
                false
            );
        }

        $grid->setActionColumnClassAttribute('table-col table-col-10');
        $grid->addEditActionColumn('admin_customer_edit', ['id' => 'id']);
        $grid->addDeleteActionColumn('admin_customer_delete', ['id' => 'id'])
            ->setConfirmMessage(t('Do you really want to remove this customer?'));

        $grid->setTheme('@ShopsysFramework/Admin/Content/Customer/listGrid.html.twig');

        return $grid;
    }
}
