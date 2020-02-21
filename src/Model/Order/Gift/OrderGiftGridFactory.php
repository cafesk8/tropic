<?php

declare(strict_types=1);

namespace App\Model\Order\Gift;

use Shopsys\FrameworkBundle\Component\Grid\Grid;
use Shopsys\FrameworkBundle\Component\Grid\GridFactory;
use Shopsys\FrameworkBundle\Component\Grid\QueryBuilderDataSource;

class OrderGiftGridFactory
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Grid\GridFactory
     */
    protected $gridFactory;

    /**
     * @var \App\Model\Order\Gift\OrderGiftRepository
     */
    protected $orderGiftRepository;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Grid\GridFactory $gridFactory
     * @param \App\Model\Order\Gift\OrderGiftRepository $orderGiftRepository
     */
    public function __construct(GridFactory $gridFactory, OrderGiftRepository $orderGiftRepository)
    {
        $this->gridFactory = $gridFactory;
        $this->orderGiftRepository = $orderGiftRepository;
    }

    /**
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Component\Grid\Grid
     */
    public function createForDomain(int $domainId): Grid
    {
        $queryBuilder = $this->orderGiftRepository->getQueryBuilderForAdminOrderGiftGrid($domainId);

        $dataSource = new QueryBuilderDataSource($queryBuilder, 'id');

        $grid = $this->gridFactory->create('orderGifts', $dataSource);

        $grid->addColumn('priceLevelWithVat', 'priceLevelWithVat', t('Hladina ceny objednávky s DPH'), true);
        $grid->addColumn('enabled', 'enabled', t('Aktivní'));
        $grid->addColumn('productsCount', 'productsCount', t('Počet nabízených produktů'));

        $grid->addEditActionColumn('admin_ordergift_edit', ['id' => 'id']);
        $grid->addDeleteActionColumn('admin_ordergift_delete', ['id' => 'id'])
            ->setConfirmMessage(t('Opravdu chcete odstranit tuto hladinu dárků?'));

        $grid->setTheme('Admin/Content/Order/Gift/listGrid.html.twig');

        return $grid;
    }
}
