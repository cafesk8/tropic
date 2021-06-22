<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Model\Customer\User\CustomerUser;
use Shopsys\FrameworkBundle\Form\Admin\Customer\User\CustomerUserFormType;
use Shopsys\FrameworkBundle\Form\Constraints\Email;
use Shopsys\FrameworkBundle\Form\DisplayOnlyType;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserFacade;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CustomerUserFormTypeExtension extends AbstractTypeExtension
{
    private CustomerUserFacade $customerUserFacade;

    private ?CustomerUser $customerUser;

    /**
     * @param \App\Model\Customer\User\CustomerUserFacade $customerUserFacade
     */
    public function __construct(
        CustomerUserFacade $customerUserFacade
    ) {
        $this->customerUserFacade = $customerUserFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->customerUser = $options['customerUser'];

        if ($this->customerUser instanceof CustomerUser) {
            $systemDataGroupBuilder = $builder->get('systemData');

            $systemDataGroupBuilder
                ->add('transferId', DisplayOnlyType::class, [
                    'label' => t('ID z IS'),
                    'data' => $this->customerUser->getTransferId() ?? t('ID nenastaveno'),
                ]);
            $builderPersonalDataGroup = $builder->get('personalData');
            $builderPersonalDataGroup
                ->add('email', EmailType::class, [
                    'constraints' => [
                        new Constraints\NotBlank(['message' => 'Please enter email']),
                        new Constraints\Length([
                            'max' => 255,
                            'maxMessage' => 'Email cannot be longer than {{ limit }} characters',
                        ]),
                        new Email(['message' => 'Please enter valid email']),
                        new Constraints\Callback([$this, 'validateUniqueEmail']),
                    ],
                    'label' => t('Email'),
                ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        yield CustomerUserFormType::class;
    }

    /**
     * @param string $email
     * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
     */
    public function validateUniqueEmail(string $email, ExecutionContextInterface $context): void
    {
        /** @var \Symfony\Component\Form\Form $form */
        $form = $context->getRoot();
        /** @var \App\Model\Customer\User\CustomerUserData $customerUserData */
        $customerUserData = $form->getData()->customerUserData;

        $domainId = $customerUserData->domainId;
        $customerUserByEmailAndDomain = $this->customerUserFacade->findCustomerUserByEmailAndDomain($email, $domainId);
        if ($customerUserByEmailAndDomain !== null && $customerUserByEmailAndDomain->getId() !== $this->customerUser->getId()) {
            $context->addViolation('The email is already registered on given domain new test');
        }
    }
}
