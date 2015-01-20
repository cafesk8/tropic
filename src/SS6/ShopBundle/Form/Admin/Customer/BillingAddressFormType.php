<?php

namespace SS6\ShopBundle\Form\Admin\Customer;

use SS6\ShopBundle\Model\Customer\BillingAddressData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints;

class BillingAddressFormType extends AbstractType {

	const VALIDATION_GROUP_COMPANY_CUSTOMER = 'companyCustomer';

	/**
	 * @return string
	 */
	public function getName() {
		return 'billingAddress';
	}

	/**
	 * @param \Symfony\Component\Form\FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('telephone', 'text', ['required' => false])
			->add('companyCustomer', 'checkbox', ['required' => false])
			->add('companyName', 'text', [
				'required' => true,
				'constraints' => [
					new Constraints\NotBlank([
						'message' => 'Vyplňte prosím název firmy',
						'groups' => [self::VALIDATION_GROUP_COMPANY_CUSTOMER],
					]),
				],
			])
			->add('companyNumber', 'text', [
				'required' => true,
				'constraints' => [
					new Constraints\NotBlank([
						'message' => 'Vyplňte prosím IČ',
						'groups' => [self::VALIDATION_GROUP_COMPANY_CUSTOMER],
					]),
				],
			])
			->add('companyTaxNumber', 'text', ['required' => false])
			->add('street', 'text', ['required' => false])
			->add('city', 'text', ['required' => false])
			->add('postcode', 'text', ['required' => false]);
	}

	public function setDefaultOptions(OptionsResolverInterface $resolver) {
		$resolver->setDefaults([
			'data_class' => BillingAddressData::class,
			'attr' => ['novalidate' => 'novalidate'],
			'validation_groups' => function (FormInterface $form) {
				$validationGroups = ['Default'];

				$customerData = $form->getData();
				/* @var $customerData \SS6\ShopBundle\Model\Customer\CustomerData */

				if ($customerData->companyCustomer) {
					$validationGroups[] = self::VALIDATION_GROUP_COMPANY_CUSTOMER;
				}

				return $validationGroups;
			},
		]);
	}

}
