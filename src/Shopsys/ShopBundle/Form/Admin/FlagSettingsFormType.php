<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Shopsys\ShopBundle\Model\Product\Flag\FlagFacade;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FlagSettingsFormType extends AbstractType
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\Flag\FlagFacade
     */
    private $flagFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Flag\FlagFacade $flagFacade
     */
    public function __construct(FlagFacade $flagFacade)
    {
        $this->flagFacade = $flagFacade;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('defaultFreeTransportAndPaymentFlag', ChoiceType::class, [
                'required' => false,
                'choices' => $this->flagFacade->getAll(),
                'choice_label' => 'name',
                'choice_value' => 'id',
                'data' => $this->flagFacade->getDefaultFlagForFreeTransportAndPayment(),
            ])
            ->add('save', SubmitType::class);
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'attr' => ['novalidate' => 'novalidate'],
            ]);
    }
}
