<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Component\Balikobot\Shipper\ShipperFacade;
use App\Component\Balikobot\Shipper\ShipperServiceFacade;
use App\Component\MergadoTransportType\MergadoTransportTypeFacade;
use App\Model\Country\CountryFacade;
use App\Model\Transport\Transport;
use App\Model\Transport\TransportFacade;
use Shopsys\FormTypesBundle\YesNoType;
use Shopsys\FrameworkBundle\Component\Router\CurrentDomainRouter;
use Shopsys\FrameworkBundle\Form\Admin\Transport\TransportFormType;
use Shopsys\FrameworkBundle\Form\GroupType;
use Shopsys\FrameworkBundle\Form\ValidationGroup;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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

    private ShipperFacade $shipperFacade;

    private ShipperServiceFacade $shipperServiceFacade;

    private CurrentDomainRouter $currentDomainRouter;

    private CountryFacade $countryFacade;

    private MergadoTransportTypeFacade $mergadoTransportTypeFacade;

    private TransportFacade $transportFacade;

    /**
     * @param \App\Component\Balikobot\Shipper\ShipperFacade $shipperFacade
     * @param \App\Component\Balikobot\Shipper\ShipperServiceFacade $shipperServiceFacade
     * @param \Shopsys\FrameworkBundle\Component\Router\CurrentDomainRouter $currentDomainRouter
     * @param \App\Model\Country\CountryFacade $countryFacade
     * @param \App\Component\MergadoTransportType\MergadoTransportTypeFacade $mergadoTransportTypeFacade
     * @param \App\Model\Transport\TransportFacade $transportFacade
     */
    public function __construct(
        ShipperFacade $shipperFacade,
        ShipperServiceFacade $shipperServiceFacade,
        CurrentDomainRouter $currentDomainRouter,
        CountryFacade $countryFacade,
        MergadoTransportTypeFacade $mergadoTransportTypeFacade,
        TransportFacade $transportFacade
    ) {
        $this->shipperFacade = $shipperFacade;
        $this->shipperServiceFacade = $shipperServiceFacade;
        $this->currentDomainRouter = $currentDomainRouter;
        $this->countryFacade = $countryFacade;
        $this->mergadoTransportTypeFacade = $mergadoTransportTypeFacade;
        $this->transportFacade = $transportFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var \App\Model\Transport\Transport|null $transport */
        $transport = $options['transport'];

        $countries = $this->countryFacade->getAll();
        $builderBasicInformationGroup = $builder->get('basicInformation');
        $builderBasicInformationGroup
            ->add('countries', ChoiceType::class, [
                'label' => t('St??ty, pro kter?? je doprava dostupn??'),
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
            ->add('zboziType', TextType::class, [
                'required' => false,
                'label' => t('Typ pro Zbo????.cz'),
            ])
            ->add('externalId', TextType::class, [
                'label' => 'ID z IS',
                'required' => false,
            ])
            ->add('trackingUrlPattern', TextType::class, [
                'label' => t('Tvar odkazu pro sledov??n?? z??silky'),
                'icon_title' => t('Jako z??stupn?? znak pro ????slo sledov??n?? z??silky zadejte %s, tedy nap??. https://tracking.ulozenka.cz/?_fid=%s'),
                'required' => false,
            ])
            ->add('bulkyAllowed', YesNoType::class, [
                'required' => false,
                'label' => t('Povolit objemn?? produkty'),
            ])
            ->add('oversizedAllowed', YesNoType::class, [
                'required' => false,
                'label' => t('Povolit nadrozm??rn?? produkty'),
            ]);

        $builder->add($this->getTransportTypeGroup($builder));
        $this->extendPricesGroup($builder, $transport);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {

            /** @var \App\Model\Transport\TransportData $transportData */
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

            /** @var \App\Model\Transport\TransportData $transportData */
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
    public static function getExtendedTypes(): iterable
    {
        yield TransportFormType::class;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function getTransportTypeGroup(FormBuilderInterface $builder): FormBuilderInterface
    {
        $builderTransportTypeGroup = $builder->create('transportTypeGroup', GroupType::class, [
            'label' => t('Mo??nosti dopravy'),
            'position' => ['after' => 'basicInformation'],
        ]);
        $builderTransportTypeGroup->add('transportType', ChoiceType::class, [
            'label' => t('Typ dopravy'),
            'choices' => [
                t('Bez osobn??ho p??evzet??') => Transport::TYPE_NONE,
                t('Bal??kobot') => Transport::TYPE_PERSONAL_TAKE_BALIKOBOT,
                t('Z??silkovna CZ') => Transport::TYPE_ZASILKOVNA_CZ,
                t('Z??silkovna SK') => Transport::TYPE_ZASILKOVNA_SK,
                t('Prodejny') => Transport::TYPE_PERSONAL_TAKE_STORE,
                t('E-mailem') => Transport::TYPE_EMAIL,
            ],
            'attr' => [
                'class' => 'js-transport-type',
            ],
            'icon_title' => t('Zm??ny se projev?? n??kolik minut po ulo??en?? z d??vodu stahov??n?? aktu??ln??ch pobo??ek.'),
        ]);
        $builderTransportTypeGroup->add('mergadoTransportType', ChoiceType::class, [
            'label' => t('Typ dopravy pro Mergado'),
            'choices' => $this->mergadoTransportTypeFacade->getMergadoTransportNamesIndexedByName(),
            'required' => false,
            'icon_title' => t('P??i nevypln??n?? nebude doprava do Mergada exportov??na.'),
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
                    'message' => 'Mus??te vybrat dopravce',
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
        $placeholderMessage = t('V??choz?? slu??ba dopravce');

        if (count($shipperServices) > 1) {
            $validationGroupForShipperService[] = new Constraints\NotBlank([
                'message' => 'Mus??te vybrat slu??bu dopravce',
                'groups' => [self::VALIDATION_GROUP_BALIKOBOT_SHIPPER_SERVICE],
            ]);

            $placeholderMessage = t('Vyberte pros??m slu??bu dopravce');
        }

        $builderTransportTypeGroup->add('balikobotShipperService', ChoiceType::class, [
            'required' => false,
            'placeholder' => $placeholderMessage,
            'label' => t('Slu??ba dopravce'),
            'choices' => array_flip($shipperServices),
            'attr' => [
                'class' => 'js-transport-select-shipper-service js-transport-depend-on-balikobot',
            ],
            'constraints' => $validationGroupForShipperService,
        ]);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \App\Model\Transport\Transport|null $transport
     */
    private function extendPricesGroup(FormBuilderInterface $builder, ?Transport $transport): void
    {
        $pricesGroup = $builder->get('prices');
        $pricesGroup->remove('pricesByDomains');

        $pricesGroup->add('pricesByDomains', TransportPricesType::class, [
            'pricesIndexedByDomainId' => $this->transportFacade->getPricesIndexedByDomainId($transport),
            'inherit_data' => true,
            'render_form_row' => false,
        ]);
    }
}
