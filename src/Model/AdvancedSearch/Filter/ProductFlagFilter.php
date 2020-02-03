<?php

declare(strict_types=1);

namespace App\Model\AdvancedSearch\Filter;

use Shopsys\FrameworkBundle\Model\AdvancedSearch\Filter\ProductFlagFilter as BaseProductFlagFilter;

/**
 * @method __construct(\App\Model\Product\Flag\FlagFacade $flagFacade)
 */
class ProductFlagFilter extends BaseProductFlagFilter
{
    /**
     * @var \App\Model\Product\Flag\FlagFacade
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
