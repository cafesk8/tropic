<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Shopsys\FrameworkBundle\Form\Admin\Product\VariantFormType;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterFacade;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints;

class VariantFormTypeExtension extends AbstractTypeExtension
{
    public const DISTINGUISHING_PARAMETER = 'distinguishingParameter';

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterFacade
     */
    private $parameterFacade;

    /**
     * ProductFormTypeExtension constructor.
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterFacade $parameterFacade
     */
    public function __construct(ParameterFacade $parameterFacade)
    {
        $this->parameterFacade = $parameterFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $allParameters = $this->parameterFacade->getAll();

        $builder
            ->add(self::DISTINGUISHING_PARAMETER, ChoiceType::class, [
                'required' => true,
                'label' => t('Rozlišující parameter'),
                'choices' => $allParameters,
                'choice_label' => 'name',
                'choice_value' => 'id',
                'placeholder' => t('Zvolte parametr'),
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType(): string
    {
        return VariantFormType::class;
    }
}
