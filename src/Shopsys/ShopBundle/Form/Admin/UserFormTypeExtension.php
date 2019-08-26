<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Shopsys\FrameworkBundle\Form\Admin\Customer\UserFormType;
use Shopsys\FrameworkBundle\Form\DisplayOnlyType;
use Shopsys\ShopBundle\Model\Customer\User;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class UserFormTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $options['user'];
        /* @var $user \Shopsys\ShopBundle\Model\Customer\User */

        if ($user instanceof User) {
            $systemDataGroupBuilder = $builder->get('systemData');

            $systemDataGroupBuilder->add('transferId', DisplayOnlyType::class, [
                'label' => t('ID z IS'),
                'data' => $user->getTransferId() ?? t('ID nenastaveno'),
            ])
            ->add('ean', DisplayOnlyType::class, [
                'label' => t('EAN věrnostní karty'),
                'data' => $user->getEan() ?? t('EAN nenastaven'),
            ])
            ->add('memberOfBushmanClub', DisplayOnlyType::class, [
                'label' => t('Členem Bushman Clubu'),
                'data' => $user->isMemberOfBushmanClub() === true ? t('Ano') : t('Ne'),
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return UserFormType::class;
    }
}
