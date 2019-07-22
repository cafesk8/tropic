<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Flag;

use Doctrine\ORM\Query\Expr\Join;
use Shopsys\FrameworkBundle\Component\Grid\Grid;
use Shopsys\FrameworkBundle\Component\Grid\QueryBuilderDataSource;
use Shopsys\FrameworkBundle\Model\Product\Flag\FlagGridFactory as BaseFlagGridFactory;

class FlagGridFactory extends BaseFlagGridFactory
{
    /**
     * @return \Shopsys\FrameworkBundle\Component\Grid\Grid
     */
    public function create(): Grid
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
            ->select('a, at')
            ->from(Flag::class, 'a')
            ->join('a.translations', 'at', Join::WITH, 'at.locale = :locale')
            ->setParameter('locale', $this->localization->getAdminLocale())
            ->orderBy('a.position', 'ASC');
        $dataSource = new QueryBuilderDataSource($queryBuilder, 'a.id');

        $grid = $this->gridFactory->create('flagList', $dataSource);

        $grid->addColumn('name', 'at.name', t('Name'), true);
        $grid->addColumn('rgbColor', 'a.rgbColor', t('Colour'), true);
        $grid->addColumn('visible', 'a.visible', t('Filter by'), true);

        $grid->setActionColumnClassAttribute('table-col table-col-10');
        $grid->addDeleteActionColumn('admin_flag_delete', ['id' => 'a.id'])
            ->setConfirmMessage(t('Do you really want to remove this flag?'));

        $grid->setTheme('@ShopsysFramework/Admin/Content/Flag/listGrid.html.twig');

        $grid->enableDragAndDrop(Flag::class);

        return $grid;
    }
}
