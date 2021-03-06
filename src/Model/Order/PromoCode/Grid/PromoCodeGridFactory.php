<?php

declare(strict_types=1);

namespace App\Model\Order\PromoCode\Grid;

use App\Model\Order\Order;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Component\Grid\Grid;
use Shopsys\FrameworkBundle\Component\Grid\GridFactory;
use Shopsys\FrameworkBundle\Component\Grid\QueryBuilderWithRowManipulatorDataSource;
use Shopsys\FrameworkBundle\Component\String\DatabaseSearching;
use Shopsys\FrameworkBundle\Component\String\TransformString;
use Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormData;
use Shopsys\FrameworkBundle\Model\Localization\Localization;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\Grid\PromoCodeGridFactory as BasePromoCodeGridFactory;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCode;
use Shopsys\FrameworkBundle\Twig\DateTimeFormatterExtension;

class PromoCodeGridFactory extends BasePromoCodeGridFactory
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    private $adminDomainTabsFacade;

    /**
     * @var \App\Twig\DateTimeFormatterExtension
     */
    private $dateTimeFormatterExtension;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Localization\Localization
     */
    protected $localization;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Component\Grid\GridFactory $gridFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     * @param \App\Twig\DateTimeFormatterExtension $dateTimeFormatterExtension
     * @param \Shopsys\FrameworkBundle\Model\Localization\Localization $localization
     */
    public function __construct(
        EntityManagerInterface $em,
        GridFactory $gridFactory,
        AdminDomainTabsFacade $adminDomainTabsFacade,
        DateTimeFormatterExtension $dateTimeFormatterExtension,
        Localization $localization
    ) {
        parent::__construct($em, $gridFactory);
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
        $this->dateTimeFormatterExtension = $dateTimeFormatterExtension;
        $this->localization = $localization;
    }

    /**
     * @param bool $withEditButton
     * @param \Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormData|null $quickSearchData
     * @return \Shopsys\FrameworkBundle\Component\Grid\Grid
     */
    public function create($withEditButton = false, ?QuickSearchFormData $quickSearchData = null): Grid
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
            ->select('pc')
            ->from(PromoCode::class, 'pc')
            ->where('pc.domainId = :selectedDomainId')
            ->setParameter('selectedDomainId', $this->adminDomainTabsFacade->getSelectedDomainId());

        if (TransformString::emptyToNull($quickSearchData->text) !== null) {
            $queryBuilder
                ->leftJoin(Order::class, 'o', 'WITH', 'o.promoCodesCodes = pc.code')
                ->andWhere('(
                    NORMALIZE(pc.code) LIKE NORMALIZE(:text)
                    OR NORMALIZE(pc.certificateSku) LIKE NORMALIZE(:text)
                    OR NORMALIZE(o.email) LIKE NORMALIZE(:text)
                )')
                ->setParameter('text', DatabaseSearching::getFullTextLikeSearchString($quickSearchData->text));
        }

        $dataSource = new QueryBuilderWithRowManipulatorDataSource(
            $queryBuilder,
            'pc.id',
            function ($row) {
                $row['pc']['usageLimit'] = $row['pc']['unlimited'] === true ? '-' : $row['pc']['usageLimit'];

                if ($row['pc']['validTo'] === null) {
                    $row['pc']['validTo'] = t('Neomezeno');
                } else {
                    $row['pc']['validTo'] = t('Do') . ' ' . $this->dateTimeFormatterExtension->formatDate($row['pc']['validTo'], $this->localization->getAdminLocale());
                }

                return $row;
            }
        );

        $grid = $this->gridFactory->create('promoCodeList', $dataSource);
        $grid->setDefaultOrder('code');

        $grid->addColumn('type', 'pc.type', t('Typ'), true);
        $grid->addColumn('code', 'pc.code', t('Code'), true);
        $grid->addColumn('percent', 'pc.percent', t('Discount'), true);
        $grid->addColumn('number_of_uses', 'pc.numberOfUses', t('Kolikr??t pou??ito'), true);
        $grid->addColumn('usage_limit', 'pc.usageLimit', t('Maxim??ln?? po??et pou??it??'), true);
        $grid->addColumn('unlimited', 'pc.validTo', t('Platnost'), true);
        $grid->addColumn('prefix', 'pc.prefix', t('Prefix'), true);

        $grid->setActionColumnClassAttribute('table-col table-col-10');

        $grid->addEditActionColumn('admin_promocode_edit', ['id' => 'pc.id']);
        $grid->addDeleteActionColumn('admin_promocode_delete', ['id' => 'pc.id'])
            ->setConfirmMessage(t('Do you really want to remove this promo code?'));

        $grid->setTheme('Admin/Content/PromoCode/listGrid.html.twig');

        return $grid;
    }
}
