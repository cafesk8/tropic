<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderGiftChoiceType extends AbstractType
{
    /**
     * @inheritDoc
     */
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'multiple' => false,
            'expanded' => true,
        ]);
    }
}
