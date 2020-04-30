<?php

declare(strict_types=1);

namespace App\Model\Order\Discount;

use Shopsys\FrameworkBundle\Component\Grid\Grid;
use Shopsys\FrameworkBundle\Component\Grid\GridFactory;
use Shopsys\FrameworkBundle\Component\Grid\QueryBuilderDataSource;

class OrderDiscountLevelGridFactory
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Grid\GridFactory
     */
    private $gridFactory;

    /**
     * @var \App\Model\Order\Discount\OrderDiscountLevelRepository
     */
    private $orderDiscountLevelRepository;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Grid\GridFactory $gridFactory
     * @param \App\Model\Order\Discount\OrderDiscountLevelRepository $orderDiscountLevelRepository
     */
    public function __construct(GridFactory $gridFactory, OrderDiscountLevelRepository $orderDiscountLevelRepository)
    {
        $this->gridFactory = $gridFactory;
        $this->orderDiscountLevelRepository = $orderDiscountLevelRepository;
    }

    /**
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Component\Grid\Grid
     */
    public function createForDomain(int $domainId): Grid
    {
        $queryBuilder = $this->orderDiscountLevelRepository->getQueryBuilderForAdminOrderDiscountLevelGrid($domainId);

        $dataSource = new QueryBuilderDataSource($queryBuilder, 'id');

        $grid = $this->gridFactory->create('orderDiscountLevels', $dataSource);

        $grid->addColumn('priceLevelWithVat', 'priceLevelWithVat', t('Hladina ceny objednávky s DPH'), true);
        $grid->addColumn('enabled', 'enabled', t('Aktivní'));
        $grid->addColumn('discountPercent', 'discountPercent', t('Procentuální sleva'));

        $grid->addEditActionColumn('admin_orderdiscountlevel_edit', ['id' => 'id']);
        $grid->addDeleteActionColumn('admin_orderdiscountlevel_delete', ['id' => 'id'])
            ->setConfirmMessage(t('Opravdu chcete odstranit tuto hladinu slevy?'));

        $grid->setTheme('Admin/Content/Order/DiscountLevel/listGrid.html.twig');

        return $grid;
    }
}
