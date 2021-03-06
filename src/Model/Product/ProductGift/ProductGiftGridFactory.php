<?php

declare(strict_types=1);

namespace App\Model\Product\ProductGift;

use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Component\Grid\Grid;
use Shopsys\FrameworkBundle\Component\Grid\GridFactory;
use Shopsys\FrameworkBundle\Component\Grid\QueryBuilderDataSource;
use Shopsys\FrameworkBundle\Model\Localization\Localization;

class ProductGiftGridFactory
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
     * @var \App\Model\Product\ProductGift\ProductGiftRepository
     */
    protected $productGiftRepository;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    private $adminDomainTabsFacade;

    /**
     * @param \App\Model\Product\ProductGift\ProductGiftRepository $productGiftRepository
     * @param \Shopsys\FrameworkBundle\Component\Grid\GridFactory $gridFactory
     * @param \Shopsys\FrameworkBundle\Model\Localization\Localization $localization
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     */
    public function __construct(
        ProductGiftRepository $productGiftRepository,
        GridFactory $gridFactory,
        Localization $localization,
        AdminDomainTabsFacade $adminDomainTabsFacade
    ) {
        $this->gridFactory = $gridFactory;
        $this->localization = $localization;
        $this->productGiftRepository = $productGiftRepository;
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Grid\Grid
     */
    public function create(): Grid
    {
        $queryBuilder = $this->productGiftRepository->getQueryBuilderForAdminProductGiftGrid(
            $this->adminDomainTabsFacade->getSelectedDomainId()
        );

        $dataSource = new QueryBuilderDataSource($queryBuilder, 'pg.id');

        $grid = $this->gridFactory->create('productGifts', $dataSource);
        $grid->setDefaultOrder('pg.gift.name');

        $grid->addColumn('title', 'pg.title', t('N??zev d??rku'));
        $grid->addColumn('name', 't.name', t('N??zev d??rku - produktu'));
        $grid->addColumn('productsCount', 'productsCount', t('Po??et produkt??, ke ker??m je d??rek p??i??azen'));
        $grid->addColumn('active', 'pg.active', t('Aktivn??'));

        $grid->setActionColumnClassAttribute('table-col table-col-10');
        $grid->addEditActionColumn('admin_productgift_edit', ['id' => 'pg.id']);
        $grid->addDeleteActionColumn('admin_productgift_delete', ['id' => 'pg.id'])
            ->setConfirmMessage(t('Opravdu chcete odstranit tento d??rek?'));

        $grid->setTheme('Admin/Content/ProductGift/listGrid.html.twig');

        return $grid;
    }
}
