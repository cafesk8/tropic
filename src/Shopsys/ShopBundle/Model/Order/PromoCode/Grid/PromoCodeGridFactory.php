<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\PromoCode\Grid;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Component\Grid\Grid;
use Shopsys\FrameworkBundle\Component\Grid\GridFactory;
use Shopsys\FrameworkBundle\Component\Grid\QueryBuilderDataSource;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\Grid\PromoCodeGridFactory as BasePromoCodeGridFactory;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCode;

class PromoCodeGridFactory extends BasePromoCodeGridFactory
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    private $adminDomainTabsFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Component\Grid\GridFactory $gridFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     */
    public function __construct(EntityManagerInterface $em, GridFactory $gridFactory, AdminDomainTabsFacade $adminDomainTabsFacade)
    {
        parent::__construct($em, $gridFactory);
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Grid\Grid
     */
    public function create(): Grid
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
            ->select('pc')
            ->from(PromoCode::class, 'pc')
            ->where('pc.domainId = :selectedDomainId')
            ->setParameter('selectedDomainId', $this->adminDomainTabsFacade->getSelectedDomainId());

        $dataSource = new QueryBuilderDataSource($queryBuilder, 'pc.id');

        $grid = $this->gridFactory->create('promoCodeList', $dataSource);
        $grid->setDefaultOrder('code');

        $grid->addColumn('type', 'pc.type', t('Typ'), true);
        $grid->addColumn('code', 'pc.code', t('Code'), true);
        $grid->addColumn('percent', 'pc.percent', t('Discount'), true);
        $grid->addColumn('number_of_uses', 'pc.numberOfUses', t('Kolikrát použito'), true);
        $grid->addColumn('usage_limit', 'pc.usageLimit', t('Maximální počet použití'), true);
        $grid->addColumn('unlimited', 'pc.unlimited', t('Neomezený'), true);
        $grid->addColumn('combinable', 'pc.combinable', t('Kombinovatelný'), true);
        $grid->addColumn('prefix', 'pc.prefix', t('Prefix'), true);

        $grid->setActionColumnClassAttribute('table-col table-col-10');

        $grid->addEditActionColumn('admin_promocode_edit', ['id' => 'pc.id']);
        $grid->addDeleteActionColumn('admin_promocode_delete', ['id' => 'pc.id'])
            ->setConfirmMessage(t('Do you really want to remove this promo code?'));

        $grid->setTheme('@ShopsysShop/Admin/Content/PromoCode/listGrid.html.twig');

        return $grid;
    }
}
