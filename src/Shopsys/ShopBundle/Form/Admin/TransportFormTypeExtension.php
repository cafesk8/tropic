<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Shopsys\FrameworkBundle\Component\Router\CurrentDomainRouter;
use Shopsys\FrameworkBundle\Form\Admin\Transport\TransportFormType;
use Shopsys\FrameworkBundle\Form\GroupType;
use Shopsys\FrameworkBundle\Form\ValidationGroup;
use Shopsys\ShopBundle\Component\Balikobot\Shipper\ShipperFacade;
use Shopsys\ShopBundle\Component\Balikobot\Shipper\ShipperServiceFacade;
use Shopsys\ShopBundle\Model\Country\CountryFacade;
use Shopsys\ShopBundle\Model\Transport\Transport;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class TransportFormTypeExtension extends AbstractTypeExtension
{
    public const VALIDATION_GROUP_BALIKOBOT = 'balikobot';
    public const VALIDATION_GROUP_BALIKOBOT_SHIPPER_SERVICE = 'balikobot_shipper_service';

    /**
     * @var \Shopsys\ShopBundle\Component\Balikobot\Shipper\ShipperFacade
     */
    private $shipperFacade;

    /**
     * @var \Shopsys\ShopBundle\Component\Balikobot\Shipper\ShipperServiceFacade
     */
    private $shipperServiceFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Router\CurrentDomainRouter
     */
    private $currentDomainRouter;

    /**
     * @var \Shopsys\ShopBundle\Model\Country\CountryFacade
     */
    private $countryFacade;

    /**
     * @param \Shopsys\ShopBundle\Component\Balikobot\Shipper\ShipperFacade $shipperFacade
     * @param \Shopsys\ShopBundle\Component\Balikobot\Shipper\ShipperServiceFacade $shipperServiceFacade
     * @param \Shopsys\FrameworkBundle\Component\Router\CurrentDomainRouter $currentDomainRouter
     * @param \Shopsys\ShopBundle\Model\Country\CountryFacade $countryFacade
     */
    public function __construct(
        ShipperFacade $shipperFacade,
        ShipperServiceFacade $shipperServiceFacade,
        CurrentDomainRouter $currentDomainRouter,
        CountryFacade $countryFacade
    ) {
        $this->shipperFacade = $shipperFacade;
        $this->shipperServiceFacade = $shipperServiceFacade;
        $this->currentDomainRouter = $currentDomainRouter;
        $this->countryFacade = $countryFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $countries = $this->countryFacade->getAll();
        $builderBasicInformationGroup = $builder->get('basicInformation');
        $builderBasicInformationGroup
            ->add('countries', ChoiceType::class, [
                'label' => t('Státy, pro které je doprava dostupná'),
                'required' => false,
                'choices' => $countries,
                'choice_label' => 'name',
                'choice_value' => 'id',
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('mallType', TextType::class, [
                'required' => false,
                'label' => t('Mall id'),
            ])
            ->add('deliveryDays', IntegerType::class, [
                'required' => true,
                'label' => t('Počet prac. dní do dodání'),
                'constraints' => [
                    new Constraints\NotBlank(['message' => 'Vyplňte prosím počet dnů do dodání']),
                    new Constraints\GreaterThanOrEqual(['value' => 0, 'message' => 'Počet dnů do dodání musí být větší nebo roven nule']),
                ],
            ])
            ->add('externalId', TextType::class, [
                'label' => 'ID z IS',
                'required' => false,
            ])
            ->add('trackingUrlPattern', TextType::class, [
                'label' => t('Tvar odkazu pro sledování zásilky'),
                'icon_title' => t('Jako zástupný znak pro číslo sledování zásilky zadejte %s, tedy např. https://tracking.ulozenka.cz/?_fid=%s'),
                'required' => false,
            ]);

        $builder->add($this->getTransportTypeGroup($builder));

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {

            /** @var \Shopsys\ShopBundle\Model\Transport\TransportData $transportData */
            $transportData = $event->getData();
            $form = $event->getForm();

            $balikobotShipper = $transportData->balikobotShipper ?: null;
            $this->addDependendElement($form, $balikobotShipper);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            if ((string)$data['transportTypeGroup']['transportType'] === Transport::TYPE_PERSONAL_TAKE_BALIKOBOT) {
                $balikobotShipper = $data['transportTypeGroup']['balikobotShipper'];
                $this->addDependendElement($form, $balikobotShipper);
            }
        });
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('validation_groups', function (FormInterface $form) {
            $validationGroups = [ValidationGroup::VALIDATION_GROUP_DEFAULT];

            /** @var \Shopsys\ShopBundle\Model\Transport\TransportData $transportData */
            $transportData = $form->getData();

            if ($transportData->transportType === Transport::TYPE_PERSONAL_TAKE_BALIKOBOT) {
                $validationGroups[] = self::VALIDATION_GROUP_BALIKOBOT;
                $validationGroups[] = self::VALIDATION_GROUP_BALIKOBOT_SHIPPER_SERVICE;
            }

            return $validationGroups;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType(): string
    {
        return TransportFormType::class;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function getTransportTypeGroup(FormBuilderInterface $builder): FormBuilderInterface
    {
        $builderTransportTypeGroup = $builder->create('transportTypeGroup', GroupType::class, [
            'label' => t('Možnosti dopravy'),
            'position' => ['after' => 'basicInformation'],
        ]);
        $builderTransportTypeGroup->add('transportType', ChoiceType::class, [
            'label' => t('Typ dopravy'),
            'choices' => [
                t('Bez osobního převzetí') => Transport::TYPE_NONE,
                t('Balíkobot') => Transport::TYPE_PERSONAL_TAKE_BALIKOBOT,
                t('Prodejny Bushman') => Transport::TYPE_PERSONAL_TAKE_STORE,
                t('E-mailem') => Transport::TYPE_EMAIL,
            ],
            'attr' => [
                'class' => 'js-transport-type',
            ],
        ]);

        return $builderTransportTypeGroup;
    }

    /**
     * @param \Symfony\Component\Form\FormInterface $form
     * @param string|null $balikobotShipper
     */
    private function addDependendElement(FormInterface $form, ?string $balikobotShipper): void
    {
        $builderTransportTypeGroup = $form->get('transportTypeGroup');

        $builderTransportTypeGroup->add('balikobotShipper', ChoiceType::class, [
            'required' => false,
            'label' => t('Dopravce'),
            'data' => $balikobotShipper,
            'placeholder' => t('Vyberte dopravce'),
            'choices' => array_flip($this->shipperFacade->getShipperNamesIndexedById()),
            'constraints' => [
                new Constraints\NotBlank([
                    'message' => 'Musíte vybrat dopravce',
                    'groups' => [self::VALIDATION_GROUP_BALIKOBOT],
                ]),
            ],
            'attr' => [
                'data-url' => $this->currentDomainRouter->generate('admin_transport_listbalikobotshipperservices'),
                'class' => 'js-transport-select-shipper js-transport-depend-on-balikobot',
            ],
        ]);

        $shipperServices = [];

        if ($balikobotShipper !== null) {
            $shipperServices = $this->shipperServiceFacade->getServicesForShipper($balikobotShipper);
        }

        $validationGroupForShipperService = [];
        $placeholderMessage = t('Výchozí služba dopravce');

        if (count($shipperServices) > 1) {
            $validationGroupForShipperService[] = new Constraints\NotBlank([
                'message' => 'Musíte vybrat službu dopravce',
                'groups' => [self::VALIDATION_GROUP_BALIKOBOT_SHIPPER_SERVICE],
            ]);

            $placeholderMessage = t('Vyberte prosím službu dopravce');
        }

        $builderTransportTypeGroup->add('balikobotShipperService', ChoiceType::class, [
            'required' => false,
            'placeholder' => $placeholderMessage,
            'label' => t('Služba dopravce'),
            'choices' => array_flip($shipperServices),
            'attr' => [
                'class' => 'js-transport-select-shipper-service js-transport-depend-on-balikobot',
            ],
            'constraints' => $validationGroupForShipperService,
        ]);
    }
}
