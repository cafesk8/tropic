<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Shopsys\FrameworkBundle\Form\Admin\AdvancedSearch\AdvancedSearchProductFilterTranslation as BaseAdvancedSearchProductFilterTranslation;
use Shopsys\FrameworkBundle\Model\AdvancedSearch\Filter\ProductCatnumFilter;
use Shopsys\ShopBundle\Model\AdvancedSearch\Filter\ProductMainVariantFilter;

class AdvancedSearchProductFilterTranslation extends BaseAdvancedSearchProductFilterTranslation
{
    public function __construct()
    {
        parent::__construct();

        $this->addFilterTranslation(ProductCatnumFilter::NAME, t('SKU'));
        $this->addFilterTranslation(ProductMainVariantFilter::NAME, t('Hlavní varianta'));
    }
}
