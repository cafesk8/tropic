<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Shopsys\ShopBundle\Model\Transfer\TransferFacade;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransferIssueSearchFormType extends AbstractType
{
    /**
     * @var \Shopsys\ShopBundle\Model\Transfer\TransferFacade
     */
    protected $transferFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Transfer\TransferFacade $transferFacade
     */
    public function __construct(TransferFacade $transferFacade)
    {
        $this->transferFacade = $transferFacade;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transfers = $this->transferFacade->getAll();

        $builder
            ->add('transfer', ChoiceType::class, [
                'required' => false,
                'choices' => $transfers,
                'choice_label' => 'identifier',
                'choice_value' => 'id',
                'placeholder' => t('-- Vyberte identifikátor přenosu --'),
            ])
            ->add('submit', SubmitType::class);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'attr' => ['novalidate' => 'novalidate'],
            ]);
    }
}
