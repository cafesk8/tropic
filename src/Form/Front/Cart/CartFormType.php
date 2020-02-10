<?php

declare(strict_types=1);

namespace App\Form\Front\Cart;

use App\Model\Cart\CartFacade;
use Shopsys\FrameworkBundle\Form\Constraints\ConstraintValue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class CartFormType extends AbstractType
{
    /**
     * @var \App\Model\Cart\CartFacade
     */
    private $cartFacade;

    /**
     * @param \App\Model\Cart\CartFacade $cartFacade
     */
    public function __construct(CartFacade $cartFacade)
    {
        $this->cartFacade = $cartFacade;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('quantities', CollectionType::class, [
                'allow_add' => true,
                'allow_delete' => true,
                'entry_type' => TextType::class,
                'constraints' => [
                    new Constraints\All([
                        'constraints' => [
                            new Constraints\NotBlank(['message' => 'Please enter quantity']),
                            new Constraints\GreaterThan(['value' => 0, 'message' => 'Quantity must be greater than {{ compared_value }}']),
                            new Constraints\LessThanOrEqual([
                                'value' => ConstraintValue::INTEGER_MAX_VALUE,
                                'message' => 'Please enter valid quantity',
                            ]),
                        ],
                    ]),
                ],
            ])
            ->add('chosenGifts', CollectionType::class, [
                'entry_type' => CollectionType::class,
                'entry_options' => [
                    'entry_type' => CheckboxType::class,
                ],
            ])
            ->add('chosenPromoProducts', CollectionType::class, [
                'entry_type' => CollectionType::class,
                'entry_options' => [
                    'entry_type' => CheckboxType::class,
                ],
            ])
            ->add('submit', SubmitType::class);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();

            $cart = $this->cartFacade->findCartOfCurrentCustomerUser();

            if ($cart !== null) {
                $this->cartFacade->getChangedCartQuantitiesBySentData($data['quantities']);
            }

            $data['quantities'] = $this->cartFacade->getCorrectedQuantitiesBySentData($data['quantities']);
            $event->setData($data);
        });
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
