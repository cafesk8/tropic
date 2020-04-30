<?php

declare(strict_types=1);

namespace App\Form\Admin;

use Shopsys\FrameworkBundle\Form\Admin\Product\Availability\AvailabilityFormType;
use Shopsys\FrameworkBundle\Form\ColorPickerType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints;

class AvailabilityFormTypeExtension extends AbstractTypeExtension
{
    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('rgbColor', ColorPickerType::class, [
            'constraints' => [
                new Constraints\NotBlank(['message' => 'Please enter flag color']),
                new Constraints\Length([
                    'max' => 7,
                    'maxMessage' => 'Flag color in must be in valid hexadecimal code e.g. #3333ff',
                ]),
            ],
        ]);
    }

    /**
     * @inheritDoc
     */
    public static function getExtendedTypes(): iterable
    {
        yield AvailabilityFormType::class;
    }
}
