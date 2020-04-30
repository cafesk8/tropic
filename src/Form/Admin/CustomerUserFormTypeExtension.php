<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Model\Customer\User\CustomerUser;
use Shopsys\FrameworkBundle\Form\Admin\Customer\User\CustomerUserFormType;
use Shopsys\FrameworkBundle\Form\DisplayOnlyType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class CustomerUserFormTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $customerUser = $options['customerUser'];
        /* @var $customerUser \App\Model\Customer\User\CustomerUser */

        if ($customerUser instanceof CustomerUser) {
            $systemDataGroupBuilder = $builder->get('systemData');

            $systemDataGroupBuilder->add('transferId', DisplayOnlyType::class, [
                'label' => t('ID z IS'),
                'data' => $customerUser->getTransferId() ?? t('ID nenastaveno'),
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
}
