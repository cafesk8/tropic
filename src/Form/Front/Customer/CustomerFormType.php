<?php

declare(strict_types=1);

namespace App\Form\Front\Customer;

use Shopsys\FrameworkBundle\Model\Customer\CustomerDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Customer\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomerFormType extends AbstractType
{
    /**
     * @var \App\Model\Customer\CustomerDataFactory
     */
    private $customerDataFactory;

    /**
     * @param \App\Model\Customer\CustomerDataFactory $customerDataFactory
     */
    public function __construct(CustomerDataFactoryInterface $customerDataFactory)
    {
        $this->customerDataFactory = $customerDataFactory;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('userData', UserFormType::class, [
                'user' => $options['user'],
            ])
            ->add('billingAddressData', BillingAddressFormType::class, [
                'domain_id' => $options['domain_id'],
            ])
            ->add('deliveryAddressData', DeliveryAddressFormType::class, [
                'domain_id' => $options['domain_id'],
                'user' => $options['user'],
            ])
            ->add('save', SubmitType::class);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired('domain_id')
            ->addAllowedTypes('domain_id', 'int')
            ->setRequired('user')
            ->addAllowedTypes('user', User::class)
            ->setDefaults([
                'empty_data' => $this->customerDataFactory->create(),
                'attr' => ['novalidate' => 'novalidate'],
            ]);
    }
}
