<?php

declare(strict_types=1);

namespace App\Model\Pricing\Vat;

use Shopsys\FrameworkBundle\Model\Pricing\Vat\VatGridFactory as BaseVatGridFactory;

/**
 * @property \App\Model\Pricing\Vat\VatFacade $vatFacade
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $em, \Shopsys\FrameworkBundle\Component\Grid\GridFactory $gridFactory, \App\Model\Pricing\Vat\VatFacade $vatFacade, \Shopsys\FrameworkBundle\Model\Pricing\PriceCalculation $priceCalculation, \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade)
 */
class VatGridFactory extends BaseVatGridFactory
{
    /**
     * @return \Shopsys\FrameworkBundle\Component\Grid\Grid
     */
    public function create()
    {
        $grid = parent::create();
        $grid->addColumn('pohodaId', 'v.pohodaId', t('ID z Pohody'));
        $grid->addColumn('pohodaName', 'v.pohodaName', t('Název z Pohody (pro XML)'));

        return $grid;
    }
}
