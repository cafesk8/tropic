<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Store;

use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Component\Grid\Grid;
use Shopsys\FrameworkBundle\Component\Grid\GridFactory;
use Shopsys\FrameworkBundle\Component\Grid\QueryBuilderDataSource;
use Shopsys\FrameworkBundle\Model\Localization\Localization;

class StoreGridFactory
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Grid\GridFactory
     */
    protected $gridFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Localization\Localization
     */
    protected $localization;

    /**
     * @var \Shopsys\ShopBundle\Model\Store\StoreRepository
     */
    protected $storeRepository;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    private $adminDomainTabsFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Store\StoreRepository $storeRepository
     * @param \Shopsys\FrameworkBundle\Component\Grid\GridFactory $gridFactory
     * @param \Shopsys\FrameworkBundle\Model\Localization\Localization $localization
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     */
    public function __construct(
        StoreRepository $storeRepository,
        GridFactory $gridFactory,
        Localization $localization,
        AdminDomainTabsFacade $adminDomainTabsFacade
    ) {
        $this->gridFactory = $gridFactory;
        $this->localization = $localization;
        $this->storeRepository = $storeRepository;
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Grid\Grid
     */
    public function create(): Grid
    {
        $queryBuilder = $this->storeRepository->getAllForDomainQueryBuilder(
            $this->adminDomainTabsFacade->getSelectedDomainId()
        );

        $dataSource = new QueryBuilderDataSource($queryBuilder, 's.id');

        $grid = $this->gridFactory->create('stores', $dataSource);
        $grid->setDefaultOrder('s.name');

        $grid->addColumn('name', 's.name', t('Name'));
        $grid->addColumn('city', 's.city', t('City'));
        $grid->addColumn('street', 's.street', t('Street'));
        $grid->addColumn('pickupPlace', 's.pickupPlace', t('Odběrné místo'));

        $grid->setActionColumnClassAttribute('table-col table-col-10');
        $grid->addEditActionColumn('admin_store_edit', ['id' => 's.id']);
        $grid->addDeleteActionColumn('admin_store_delete', ['id' => 's.id'])
            ->setConfirmMessage(t('Do you really want to remove this store?'));

        $grid->setTheme('@ShopsysShop/Admin/Content/Store/listGrid.html.twig');

        return $grid;
    }
}
