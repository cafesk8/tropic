<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\AdvancedSearch\Filter;

use Shopsys\FrameworkBundle\Model\AdvancedSearch\Filter\ProductFlagFilter as BaseProductFlagFilter;

class ProductFlagFilter extends BaseProductFlagFilter
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\Flag\FlagFacade
     */
    protected $flagFacade;

    /**
     * {@inheritdoc}
     */
    public function getValueFormOptions()
    {
        return [
            'expanded' => false,
            'multiple' => false,
            'choices' => $this->flagFacade->getAllExceptFreeTransportFlag(),
            'choice_label' => 'name',
            'choice_value' => 'id',
        ];
    }
}
