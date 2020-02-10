<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Model\AdvancedSearch\Filter\ProductMainVariantFilter;
use App\Model\AdvancedSearch\Filter\ProductParameterFilter;
use App\Model\AdvancedSearch\Filter\ProductVariantTypeNoneFilter;
use Shopsys\FrameworkBundle\Form\Admin\AdvancedSearch\AdvancedSearchProductFilterTranslation as BaseAdvancedSearchProductFilterTranslation;
use Shopsys\FrameworkBundle\Model\AdvancedSearch\Filter\ProductCatnumFilter;

class AdvancedSearchProductFilterTranslation extends BaseAdvancedSearchProductFilterTranslation
{
    public function __construct()
    {
        parent::__construct();

        $this->addFilterTranslation(ProductCatnumFilter::NAME, t('SKU'));
        $this->addFilterTranslation(ProductMainVariantFilter::NAME, t('Hlavní varianta'));
        $this->addFilterTranslation(ProductVariantTypeNoneFilter::NAME, t('Samostatný produkt (ne hlavní a ne vedlejší varianta)'));
        $this->addFilterTranslation(ProductParameterFilter::NAME, t('Parametr'));
    }
}
