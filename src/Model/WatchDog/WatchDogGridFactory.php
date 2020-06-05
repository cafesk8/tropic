<?php

declare(strict_types=1);

namespace App\Model\WatchDog;

use App\Model\Pricing\Group\PricingGroup;
use App\Model\Product\Product;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Shopsys\FrameworkBundle\Component\Grid\DataSourceInterface;
use Shopsys\FrameworkBundle\Component\Grid\Grid;
use Shopsys\FrameworkBundle\Component\Grid\GridFactory;
use Shopsys\FrameworkBundle\Component\Grid\GridFactoryInterface;
use Shopsys\FrameworkBundle\Component\Grid\QueryBuilderDataSource;
use Shopsys\FrameworkBundle\Component\Setting\SettingValue;
use Shopsys\FrameworkBundle\Model\Localization\Localization;
use Shopsys\FrameworkBundle\Model\Pricing\PricingSetting;
use Shopsys\FrameworkBundle\Model\Product\ProductTranslation;

class WatchDogGridFactory implements GridFactoryInterface
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Grid\GridFactory
     */
    private $gridFactory;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Localization\Localization
     */
    private $localization;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Grid\GridFactory $gridFactory
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Localization\Localization $localization
     */
    public function __construct(GridFactory $gridFactory, EntityManagerInterface $em, Localization $localization)
    {
        $this->gridFactory = $gridFactory;
        $this->em = $em;
        $this->localization = $localization;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Grid\Grid
     */
    public function create(): Grid
    {
        $grid = $this->gridFactory->create('watchDogGrid', $this->createAndGetDataSource());
        $grid->addColumn('createdAt', 'wd.createdAt', t('Vytvořeno'));
        $grid->addColumn('email', 'wd.email', t('Email'));
        $grid->addColumn('productCode', 'p.catnum', 'Kód produktu');
        $grid->addColumn('productName', 'pt.name', 'Název produktu');
        $grid->addColumn('availabilityWatcher', 'wd.availabilityWatcher', 'Hlídání dostupnosti');
        $grid->addColumn('priceWatcher', 'wd.priceWatcher', 'Hlídání ceny');
        $grid->addColumn('targetedDiscount', 'wd.targetedDiscount', 'Částka');
        $grid->setTheme('Admin/Content/WatchDog/listGrid.html.twig');
        $grid->enablePaging();

        return $grid;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Grid\DataSourceInterface
     */
    private function createAndGetDataSource(): DataSourceInterface
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder->select('wd', 'p', 'pt', 'sv.value AS currencyId')
            ->from(WatchDog::class, 'wd')
            ->join(Product::class, 'p', Join::WITH, 'p = wd.product')
            ->join(ProductTranslation::class, 'pt', Join::WITH, 'p = pt.translatable')
            ->join(PricingGroup::class, 'pg', Join::WITH, 'pg = wd.pricingGroup')
            ->join(SettingValue::class, 'sv', Join::WITH, 'sv.domainId = pg.domainId')
            ->where('pt.locale = :locale')
            ->andWhere('sv.name = :defaultDomainCurrencyId')
            ->setParameter('locale', $this->localization->getAdminLocale())
            ->setParameter('defaultDomainCurrencyId', PricingSetting::DEFAULT_DOMAIN_CURRENCY);

        return new QueryBuilderDataSource($queryBuilder, 'wd.id');
    }
}
