<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\PromoProduct;

use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Component\Grid\Grid;
use Shopsys\FrameworkBundle\Component\Grid\GridFactory;
use Shopsys\FrameworkBundle\Component\Grid\QueryBuilderDataSource;

class PromoProductGridFactory
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Grid\GridFactory
     */
    protected $gridFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProductRepository
     */
    protected $promoProductRepository;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    private $adminDomainTabsFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProductRepository $promoProductRepository
     * @param \Shopsys\FrameworkBundle\Component\Grid\GridFactory $gridFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     */
    public function __construct(
        PromoProductRepository $promoProductRepository,
        GridFactory $gridFactory,
        AdminDomainTabsFacade $adminDomainTabsFacade
    ) {
        $this->promoProductRepository = $promoProductRepository;
        $this->gridFactory = $gridFactory;
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Grid\Grid
     */
    public function create(): Grid
    {
        $queryBuilder = $this->promoProductRepository->getQueryBuilderForAdminPromoProductGrid(
            $this->adminDomainTabsFacade->getSelectedDomainId()
        );

        $dataSource = new QueryBuilderDataSource($queryBuilder, 'pp.id');

        $grid = $this->gridFactory->create('promoProducts', $dataSource);
        $grid->setDefaultOrder('p.name');

        $grid->addColumn('name', 'p.name', t('Název výchozího produktu'));
        $grid->addColumn('price', 'pp.price', t('Cena promo produktu'));
        $grid->addColumn('minimalCartPrice', 'pp.minimalCartPrice', t('Minimální cena košíku'));

        $grid->setActionColumnClassAttribute('table-col table-col-10');
        $grid->addEditActionColumn('admin_promoproduct_edit', ['id' => 'pg.id']);
        $grid->addDeleteActionColumn('admin_promoproduct_delete', ['id' => 'pg.id'])
            ->setConfirmMessage(t('Opravdu chcete odstranit tento promo produkt?'));

        $grid->setTheme('@ShopsysShop/Admin/Content/PromoProduct/listGrid.html.twig');

        return $grid;
    }
}
