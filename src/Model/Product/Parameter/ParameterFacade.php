<?php

declare(strict_types=1);

namespace App\Model\Product\Parameter;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter as BaseParameter;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterFacade as BaseParameterFacade;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @method \App\Model\Product\Parameter\Parameter getById(int $parameterId)
 * @method \App\Model\Product\Parameter\Parameter[] getAll()
 * @method \App\Model\Product\Parameter\Parameter create(\App\Model\Product\Parameter\ParameterData $parameterData)
 * @method \App\Model\Product\Parameter\Parameter|null findParameterByNames(string[] $namesByLocale)
 * @method \App\Model\Product\Parameter\ParameterValue getParameterValueByValueTextAndLocale(string $valueText, string $locale)
 * @method dispatchParameterEvent(\App\Model\Product\Parameter\Parameter $parameter, string $eventType)
 * @method \App\Model\Product\Parameter\Parameter edit(int $parameterId, \App\Model\Product\Parameter\ParameterData $parameterData)
 */
class ParameterFacade extends BaseParameterFacade
{
    public const PARAMETER_COLOR = [
        'cs' => 'Barva',
        'sk' => 'Farba',
        'de' => 'Farbe',
        'en' => 'Color',
    ];

    public const PARAMETER_SIZE = [
        'cs' => 'Velikost',
        'sk' => 'Velikosť',
        'de' => 'Größe',
        'en' => 'Size',
    ];

    /**
     * @var \App\Model\Product\Parameter\ParameterRepository
     */
    protected $parameterRepository;

    /**
     * @var \App\Model\Product\Parameter\ParameterDataFactory
     */
    private $parameterDataFactory;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Product\Parameter\ParameterRepository $parameterRepository
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterFactoryInterface $parameterFactory
     * @param \App\Model\Product\Parameter\ParameterDataFactory $parameterDataFactory
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        EntityManagerInterface $em,
        ParameterRepository $parameterRepository,
        ParameterFactoryInterface $parameterFactory,
        ParameterDataFactoryInterface $parameterDataFactory,
        EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct($em, $parameterRepository, $parameterFactory, $eventDispatcher);

        $this->parameterDataFactory = $parameterDataFactory;
    }

    /**
     * @param int $parameterId
     */
    public function deleteById($parameterId)
    {
        $parameter = $this->parameterRepository->getById($parameterId);

        $this->em->remove($parameter);
        $this->em->flush();
    }

    /**
     * @param string[] $parameterNamesByLocale
     * @param string|null $type
     * @param string|null $mallId
     * @param bool $visible
     * @param bool $visibleOnFrontend
     * @return \App\Model\Product\Parameter\Parameter
     */
    public function findOrCreateParameterByNames(
        array $parameterNamesByLocale,
        ?string $type = Parameter::TYPE_DEFAULT,
        ?string $mallId = null,
        bool $visible = true,
        bool $visibleOnFrontend = true
    ): BaseParameter {
        $parameter = $this->findParameterByNames($parameterNamesByLocale);

        if ($parameter === null) {
            /** @var \App\Model\Product\Parameter\ParameterData $parameterData */
            $parameterData = $this->parameterDataFactory->create();
            $parameterData->name = $parameterNamesByLocale;
            $parameterData->visible = $visible;
            $parameterData->type = $type;
            $parameterData->mallId = $mallId;
            $parameterData->visibleOnFrontend = $visibleOnFrontend;
            $parameter = $this->create($parameterData);
        }

        return $parameter;
    }

    /**
     * @return \App\Model\Product\Parameter\Parameter|null
     */
    public function findColorParameter(): ?BaseParameter
    {
        return $this->findParameterByNames(self::PARAMETER_COLOR);
    }

    /**
     * @return \App\Model\Product\Parameter\Parameter|null
     */
    public function findSizeParameter(): ?BaseParameter
    {
        return $this->findParameterByNames(self::PARAMETER_SIZE);
    }

    /**
     * @return \App\Model\Product\Parameter\Parameter
     */
    public function getColorParameter(): Parameter
    {
        return $this->findOrCreateParameterByNames(self::PARAMETER_COLOR, Parameter::TYPE_COLOR);
    }

    /**
     * @return \App\Model\Product\Parameter\Parameter
     */
    public function getSizeParameter(): Parameter
    {
        return $this->findOrCreateParameterByNames(self::PARAMETER_SIZE, Parameter::TYPE_SIZE);
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return array|\App\Model\Product\Parameter\ParameterValue[]
     */
    public function getParameterValuesBatch(int $limit, int $offset): array
    {
        return $this->parameterRepository->getParameterValuesBatch($limit, $offset);
    }

    /**
     * @param int $id
     * @return \App\Model\Product\Parameter\ParameterValue
     */
    public function getParameterValueById(int $id): ParameterValue
    {
        return $this->parameterRepository->getParameterValueById($id);
    }

    /**
     * @param \App\Model\Product\Parameter\ParameterValue $parameterValue
     * @param \App\Model\Product\Parameter\ParameterValueData $parameterValueData
     * @return \App\Model\Product\Parameter\ParameterValue
     */
    public function editParameterValue(ParameterValue $parameterValue, ParameterValueData $parameterValueData): ParameterValue
    {
        $parameterValue->edit($parameterValueData);
        $this->em->flush();

        return $parameterValue;
    }

    /**
     * @param string $locale
     * @return \App\Model\Product\Parameter\Parameter[]
     */
    public function getAllOrderedByName(string $locale): array
    {
        $parameters = $this->getAll();
        usort($parameters, function (Parameter $parameter1, Parameter $parameter2) use ($locale) {
            return strcasecmp($parameter1->getName($locale), $parameter2->getName($locale));
        });

        return $parameters;
    }
}
