<?php

declare(strict_types=1);

namespace App\Command;

use App\Component\Domain\DomainHelper;
use App\Model\Product\Parameter\ParameterFacade;
use App\Model\Product\Parameter\ParameterRepository;
use App\Model\Product\Parameter\ParameterValueDataFactory;
use App\Model\Product\Parameter\ProductParameterValueDataFactory;
use App\Model\Product\ProductData;
use App\Model\Product\ProductDataFactory;
use App\Model\Product\ProductFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixParametersValuesForOtherLocalesCommand extends Command
{
    private const KEY_CREATE_PARAMETER = 'create_parameter';
    private const KEY_PARAMETER = 'parameter';
    private const KEY_CS_VALUE = 'value';
    private const OPTION_NAME_PARAMETER_TYPE = 'parameter-type';
    private const BATCH_SIZE = 50;

    /**
     * @var string
     */
    protected static $defaultName = 'shopsys:fix:parameters-values-for-other-locales';

    /**
     * @var \App\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \App\Model\Product\Parameter\ParameterRepository
     */
    private $parameterRepository;

    /**
     * @var \App\Model\Product\Parameter\ParameterFacade
     */
    private $parameterFacade;

    /**
     * @var \App\Model\Product\Parameter\ParameterValueDataFactory
     */
    private $parameterValueDataFactory;

    /**
     * @var \App\Model\Product\Parameter\ProductParameterValueDataFactory
     */
    private $productParameterValueDataFactory;

    /**
     * @var \App\Model\Product\ProductDataFactory
     */
    private $productDataFactory;

    /**
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Product\Parameter\ParameterRepository $parameterRepository
     * @param \App\Model\Product\Parameter\ParameterValueDataFactory $parameterValueDataFactory
     * @param \App\Model\Product\Parameter\ProductParameterValueDataFactory $productParameterValueDataFactory
     * @param \App\Model\Product\ProductDataFactory $productDataFactory
     * @param \App\Model\Product\Parameter\ParameterFacade $parameterFacade
     */
    public function __construct(
        ProductFacade $productFacade,
        ParameterRepository $parameterRepository,
        ParameterValueDataFactory $parameterValueDataFactory,
        ProductParameterValueDataFactory $productParameterValueDataFactory,
        ProductDataFactory $productDataFactory,
        ParameterFacade $parameterFacade
    ) {
        parent::__construct();

        $this->productFacade = $productFacade;
        $this->parameterRepository = $parameterRepository;
        $this->parameterValueDataFactory = $parameterValueDataFactory;
        $this->productParameterValueDataFactory = $productParameterValueDataFactory;
        $this->productDataFactory = $productDataFactory;
        $this->parameterFacade = $parameterFacade;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setDescription('Fix parameters for sk and en locales');
        $this->setDefinition([
            new InputOption(self::OPTION_NAME_PARAMETER_TYPE, null, InputOption::VALUE_REQUIRED, 'Parameter type: color or size'),
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mainVariantProducts = $this->productFacade->getAllMainVariantProductsWithoutSkOrDeParameters(
            $input->getOption(self::OPTION_NAME_PARAMETER_TYPE),
            self::BATCH_SIZE
        );

        $parameterSize = $this->parameterFacade->getSizeParameter();
        $parameterColor = $this->parameterFacade->getColorParameter();

        foreach ($mainVariantProducts as $mainVariantProduct) {
            $output->writeln($mainVariantProduct->getId());
            $productData = $this->productDataFactory->createFromProduct($mainVariantProduct);

            foreach ([DomainHelper::SLOVAK_LOCALE, DomainHelper::ENGLISH_LOCALE] as $locale) {
                $productParametersByLocale = $this->parameterRepository->getAllProductParameterValuesByProductSortedByName($mainVariantProduct, $locale);
                $this->updateProductByParameterIdAndLocale($output, $productParametersByLocale, $productData, $parameterSize->getId(), $locale);
                $this->updateProductByParameterIdAndLocale($output, $productParametersByLocale, $productData, $parameterColor->getId(), $locale);
            }

            $this->productFacade->edit($mainVariantProduct, $productData);
        }

        return 0;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param array $productParametersByLocale
     * @param \App\Model\Product\ProductData $productData
     * @param int $parameterId
     * @param string $fixForLocale
     */
    private function updateProductByParameterIdAndLocale(OutputInterface $output, array $productParametersByLocale, ProductData $productData, int $parameterId, string $fixForLocale): void
    {
        $prerequisite = $this->getPrerequisite($productParametersByLocale, $parameterId, $fixForLocale);

        if ($prerequisite[self::KEY_CREATE_PARAMETER] === true && $prerequisite[self::KEY_CS_VALUE] !== null) {
            $parameterValueData = $this->parameterValueDataFactory->createFromParameterValue($prerequisite[self::KEY_CS_VALUE]);
            $parameterValueData->locale = $fixForLocale;

            $productParameterValueData = $this->productParameterValueDataFactory->create();
            $productParameterValueData->parameter = $prerequisite[self::KEY_PARAMETER];
            $productParameterValueData->parameterValueData = $parameterValueData;

            $productData->parameters[] = $productParameterValueData;
            $output->writeln(sprintf('Add value for parameter with id %s an locale %s', $parameterId, $fixForLocale));
        } else {
            $output->writeln('parameters are OK');
        }
    }

    /**
     * @param array $productParametersByLocale
     * @param int $parameterId
     * @param string $fixForLocale
     * @return array
     */
    private function getPrerequisite(array $productParametersByLocale, int $parameterId, string $fixForLocale): array
    {
        $prerequisite = [
            self::KEY_CREATE_PARAMETER => true,
            self::KEY_PARAMETER => null,
            self::KEY_CS_VALUE => null,
        ];

        foreach ($productParametersByLocale as $productParameterValue) {
            if ($productParameterValue->getParameter()->getId() === $parameterId) {
                $prerequisite[self::KEY_PARAMETER] = $productParameterValue->getParameter();
                if ($productParameterValue->getValue()->getLocale() === DomainHelper::CZECH_LOCALE) {
                    $prerequisite[self::KEY_CS_VALUE] = $productParameterValue->getValue();
                }
                if ($productParameterValue->getValue()->getLocale() === $fixForLocale) {
                    $prerequisite[self::KEY_CREATE_PARAMETER] = false;
                }
            }
        }

        return $prerequisite;
    }
}
