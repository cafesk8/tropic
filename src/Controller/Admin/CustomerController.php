<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Component\Grid\Grid;
use Shopsys\FrameworkBundle\Component\Grid\MoneyConvertingDataSourceDecorator;
use Shopsys\FrameworkBundle\Component\Grid\QueryBuilderDataSource;
use Shopsys\FrameworkBundle\Controller\Admin\CustomerController as BaseCustomerController;
use Shopsys\FrameworkBundle\Form\Admin\Customer\User\CustomerUserUpdateFormType;
use Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormData;
use Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @property \App\Model\Customer\User\CustomerUserDataFactory $customerUserDataFactory
 * @property \App\Model\Customer\User\CustomerUserFacade $customerUserFacade
 * @property \App\Model\Order\OrderFacade $orderFacade
 * @property \App\Model\Customer\User\CustomerUserUpdateDataFactory $customerUserUpdateDataFactory
 * @method string getSsoLoginAsCustomerUserUrl(\App\Model\Customer\User\CustomerUser $customerUser)
 * @method __construct(\App\Model\Customer\User\CustomerUserDataFactory $customerUserDataFactory, \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserListAdminFacade $customerUserListAdminFacade, \App\Model\Customer\User\CustomerUserFacade $customerUserFacade, \Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider $breadcrumbOverrider, \Shopsys\FrameworkBundle\Model\Administrator\AdministratorGridFacade $administratorGridFacade, \Shopsys\FrameworkBundle\Component\Grid\GridFactory $gridFactory, \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade, \App\Model\Order\OrderFacade $orderFacade, \Shopsys\FrameworkBundle\Model\Security\LoginAsUserFacade $loginAsUserFacade, \Shopsys\FrameworkBundle\Component\Router\DomainRouterFactory $domainRouterFactory, \App\Model\Customer\User\CustomerUserUpdateDataFactory $customerUserUpdateDataFactory)
 */
class CustomerController extends BaseCustomerController
{
    /**
     * @Route("/customer/edit/{id}", requirements={"id" = "\d+"})
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $id): Response
    {
        $customerUser = $this->customerUserFacade->getCustomerUserById($id);
        $customerUserUpdateData = $this->customerUserUpdateDataFactory->createFromCustomerUser($customerUser);

        $form = $this->createForm(CustomerUserUpdateFormType::class, $customerUserUpdateData, [
            'customerUser' => $customerUser,
            'domain_id' => $this->adminDomainTabsFacade->getSelectedDomainId(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->customerUserFacade->editByAdmin((int)$id, $customerUserUpdateData);

            $this->addSuccessFlashTwig(
                t('Customer <strong><a href="{{ url }}">{{ name }}</a></strong> modified'),
                [
                    'name' => $customerUser->getFullName(),
                    'url' => $this->generateUrl('admin_customer_edit', ['id' => $customerUser->getId()]),
                ]
            );

            return $this->redirectToRoute('admin_customer_list');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addErrorFlashTwig(t('Please check the correctness of all data filled.'));
        }

        $this->breadcrumbOverrider->overrideLastItem(t('Editing customer - %name%', ['%name%' => $customerUser->getFullName()]));

        $orders = $this->orderFacade->getCustomerUserOrderList($customerUser);

        return $this->render('@ShopsysFramework/Admin/Content/Customer/edit.html.twig', [
            'form' => $form->createView(),
            'customerUser' => $customerUser,
            'orders' => $orders,
            'ssoLoginAsUserUrl' => $this->getSsoLoginAsCustomerUserUrl($customerUser),
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
        /* @var $administrator \App\Model\Administrator\Administrator */

        $quickSearchForm = $this->createForm(QuickSearchFormType::class, new QuickSearchFormData());
        $quickSearchForm->handleRequest($request);

        $queryBuilder = $this->customerUserListAdminFacade->getCustomerUserListQueryBuilderByQuickSearchData(
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
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
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
        $grid->addColumn('telephone', 'u.telephone', t('Telephone'), true);
        $grid->addColumn('email', 'u.email', t('E-mail'), true);
        $grid->addColumn('pricingGroup', 'pricingGroup', t('Pricing group'), true);
        $grid->addColumn('orders_count', 'ordersCount', t('Number of orders'), true)->setClassAttribute('text-right');
        $grid->addColumn('orders_sum_price', 'ordersSumPrice', t('Orders value'), true)
            ->setClassAttribute('text-right');
        $grid->addColumn('last_order_at', 'lastOrderAt', t('Last order'), true)
            ->setClassAttribute('text-right');

        $grid->addColumn('export_status', 'u.exportStatus', t('Stav exportu do IS'));

        $grid->setActionColumnClassAttribute('table-col table-col-10');
        $grid->addEditActionColumn('admin_customer_edit', ['id' => 'id']);
        $grid->addDeleteActionColumn('admin_customer_delete', ['id' => 'id'])
            ->setConfirmMessage(t('Do you really want to remove this customer?'));

        $grid->setTheme('@ShopsysFramework/Admin/Content/Customer/listGrid.html.twig');

        return $grid;
    }
}
