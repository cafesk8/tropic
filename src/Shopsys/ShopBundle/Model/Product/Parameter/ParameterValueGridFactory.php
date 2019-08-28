<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Parameter;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Shopsys\FrameworkBundle\Component\Grid\Grid;
use Shopsys\FrameworkBundle\Component\Grid\GridFactory;
use Shopsys\FrameworkBundle\Component\Grid\GridFactoryInterface;
use Shopsys\FrameworkBundle\Component\Grid\QueryBuilderDataSource;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue;

class ParameterValueGridFactory implements GridFactoryInterface
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Grid\GridFactory
     */
    private $gridFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Parameter\AdminSelectedParameter
     */
    private $adminSelectedParameter;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Component\Grid\GridFactory $gridFactory
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\AdminSelectedParameter $adminSelectedParameter
     */
    public function __construct(
        EntityManagerInterface $em,
        GridFactory $gridFactory,
        AdminSelectedParameter $adminSelectedParameter
    ) {
        $this->em = $em;
        $this->gridFactory = $gridFactory;
        $this->adminSelectedParameter = $adminSelectedParameter;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Grid\Grid
     */
    public function create(): Grid
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
            ->select('pv')
            ->from(ParameterValue::class, 'pv')
            ->join(ProductParameterValue::class, 'ppv', Join::WITH, 'pv = ppv.value')
            ->where('ppv.parameter = :parameterId')
            ->orderBy('pv.text')
            ->groupBy('pv.id')
            ->setParameter('parameterId', $this->adminSelectedParameter->getSelectedParameter());
        $dataSource = new QueryBuilderDataSource($queryBuilder, 'pv.id');

        $grid = $this->gridFactory->create('parameterValueList', $dataSource);

        $grid->addColumn('text', 'pv.text', t('Hodnota'), true);
        $grid->addColumn('rgb', 'pv.rgb', t('Barva'), true);
        $grid->addColumn('hsFeedId', 'pv.hsFeedId', t('HS feed ID'), true);
        $grid->addColumn('mallName', 'pv.mallName', t('Mall hodnota'), true);
        $grid->addColumn('locale', 'pv.locale', t('Jazyk'), true);

        $grid->setTheme('@ShopsysShop/Admin/Content/ParameterValue/listGrid.html.twig');

        return $grid;
    }
}
