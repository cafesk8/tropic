<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin\AdvancedSearch;

use Shopsys\FrameworkBundle\Form\Admin\AdvancedSearch\AdvancedSearchOrderFilterTranslation as BaseAdvancedSearchOrderFilterTranslation;
use Shopsys\ShopBundle\Model\AdvancedSearchOrder\Filter\OrderTransportFilter;

class AdvancedSearchOrderFilterTranslation extends BaseAdvancedSearchOrderFilterTranslation
{
    public function __construct()
    {
        parent::__construct();

        $this->addFilterTranslation(OrderTransportFilter::NAME, t('Doprava objednávky'));
    }
}
