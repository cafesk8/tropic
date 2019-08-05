<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Shopsys\FrameworkBundle\Form\Admin\AdvancedSearch\AdvancedSearchProductFilterTranslation as BaseAdvancedSearchProductFilterTranslation;
use Shopsys\FrameworkBundle\Model\AdvancedSearch\Filter\ProductCatnumFilter;

class AdvancedSearchProductFilterTranslation extends BaseAdvancedSearchProductFilterTranslation
{
    public function __construct()
    {
        parent::__construct();

        $this->addFilterTranslation(ProductCatnumFilter::NAME, t('SKU'));
    }
}
