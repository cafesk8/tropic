<?php

declare(strict_types=1);

namespace App\Form\Admin\AdvancedSearch;

use App\Model\AdvancedSearchOrder\Filter\OrderTransportFilter;
use Shopsys\FrameworkBundle\Form\Admin\AdvancedSearch\AdvancedSearchOrderFilterTranslation as BaseAdvancedSearchOrderFilterTranslation;

class AdvancedSearchOrderFilterTranslation extends BaseAdvancedSearchOrderFilterTranslation
{
    public function __construct()
    {
        parent::__construct();

        $this->addFilterTranslation(OrderTransportFilter::NAME, t('Doprava objednávky'));
    }
}
