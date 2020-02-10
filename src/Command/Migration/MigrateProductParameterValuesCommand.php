<?php

declare(strict_types=1);

namespace App\Command\Migration;

use App\Command\Migration\Exception\MigrationDataNotFoundException;
use App\Component\Domain\DomainHelper;
use App\Model\Product\Parameter\Parameter;
use App\Model\Product\Parameter\ParameterFacade;
use App\Model\Product\Parameter\ParameterValueDataFactory;
use App\Model\Product\Product;
use App\Model\Product\ProductData;
use App\Model\Product\ProductDataFactory;
use App\Model\Product\ProductFacade;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueDataFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateProductParameterValuesCommand extends Command
{
    private const BATCH_LIMIT = 10;

    /**
     * @var string
     */
    protected static $defaultName = 'shopsys:migrate:product-parameter-values';

    /**
     * @var \Doctrine\DBAL\Driver\Connection
     */
    private $connection;

    /**
     * @var \App\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \App\Model\Product\ProductDataFactory
     */
    private $productDataFactory;

    /**
     * @var \App\Model\Product\Parameter\ParameterValueDataFactory
     */
    private $parameterValueDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueDataFactory
     */
    private $productParameterValueDataFactory;

    /**
     * @var \App\Model\Product\Parameter\ParameterFacade
     */
    private $parameterFacade;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param \Doctrine\DBAL\Driver\Connection $connection
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Product\ProductDataFactory $productDataFactory
     * @param \App\Model\Product\Parameter\ParameterValueDataFactory $parameterValueDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueDataFactory $productParameterValueDataFactory
     * @param \App\Model\Product\Parameter\ParameterFacade $parameterFacade
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     */
    public function __construct(
        Connection $connection,
        ProductFacade $productFacade,
        ProductDataFactory $productDataFactory,
        ParameterValueDataFactory $parameterValueDataFactory,
        ProductParameterValueDataFactory $productParameterValueDataFactory,
        ParameterFacade $parameterFacade,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();

        $this->connection = $connection;
        $this->productFacade = $productFacade;
        $this->productDataFactory = $productDataFactory;
        $this->parameterValueDataFactory = $parameterValueDataFactory;
        $this->productParameterValueDataFactory = $productParameterValueDataFactory;
        $this->parameterFacade = $parameterFacade;
        $this->entityManager = $entityManager;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setDescription('Migrate parameter values');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $symfonyStyleIo = new SymfonyStyle($input, $output);

        $page = 0;
        do {
            $products = $this->productFacade->getWithEan(self::BATCH_LIMIT, $page);
            $productsCount = count($products);
            $page++;
            foreach ($products as $product) {
                $this->entityManager->beginTransaction();
                try {
                    $productsData = $this->mapProductParameterValueData($product);
                    $this->productFacade->edit($product, $productsData);

                    $symfonyStyleIo->success(sprintf(
                        'Parameter values for product with ID `%s` was migrated',
                        $product->getId()
                    ));
                    $this->entityManager->commit();
                } catch (MigrationDataNotFoundException | Exception $exception) {
                    $symfonyStyleIo->error($exception->getMessage());
                    if ($this->entityManager->isOpen()) {
                        $this->entityManager->rollback();
                    }
                }
            }
            $this->entityManager->clear();
        } while ($productsCount > 0);

        return 0;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return \App\Model\Product\ProductData
     */
    private function mapProductParameterValueData(Product $product): ProductData
    {
        $migrateParameterValuesData = $this->getMigrateProductParameterValues($product);

        $productData = $this->productDataFactory->createFromProduct($product);

        $filteredParameters = $this->filterMallProductParameters($productData->parameters);
        $productData->parameters = $filteredParameters;

        foreach ($migrateParameterValuesData as $migrateParameterValueData) {
            $parameter = $this->getParameter($migrateParameterValueData['parameterName']);
            foreach (DomainHelper::LOCALES as $locale) {
                $parameterValueData = $this->parameterValueDataFactory->create();
                $parameterValueData->locale = $locale;
                $parameterValueData->text = $migrateParameterValueData['parameterValue'];

                $productParameterValueData = $this->productParameterValueDataFactory->create();
                $productParameterValueData->parameter = $parameter;
                $productParameterValueData->parameterValueData = $parameterValueData;

                $productData->parameters[] = $productParameterValueData;
            }
        }

        return $productData;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return string[][]
     */
    private function getMigrateProductParameterValues(Product $product): array
    {
        $sql = 'SELECT ttd.id_parameter AS parameterName, ttd.parameter_value AS parameterValue 
            FROM `sklad_varianty` sv
            JOIN `mallcz_product_parameters` ttd ON sv.nid = ttd.id_product
            WHERE sv.ean = :ean
            GROUP BY sv.ean, ttd.id_parameter';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('ean', $product->getEan());
        $stmt->execute();

        $migrateProductData = $stmt->fetchAll();
        if (count($migrateProductData) === 0) {
            throw new MigrationDataNotFoundException(sprintf('No data found for product with ID `%s`', $product->getId()));
        }
        return $migrateProductData;
    }

    /**
     * @param string $parameterName
     * @return \App\Model\Product\Parameter\Parameter
     */
    private function getParameter(string $parameterName): Parameter
    {
        $parameterNames = [];
        foreach (DomainHelper::LOCALES as $locale) {
            $parameterNames[$locale] = $parameterName;
        }

        return $this->parameterFacade->findOrCreateParameterByNames(
            $parameterNames,
            Parameter::TYPE_DEFAULT,
            $parameterName,
            false,
            false
        );
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueData[] $productParameterValues
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueData[]
     */
    private function filterMallProductParameters(array $productParameterValues): array
    {
        $filteredProductParameterValues = [];
        foreach ($productParameterValues as $productParameterValue) {
            /** @var \App\Model\Product\Parameter\Parameter $parameter */
            $parameter = $productParameterValue->parameter;

            if ($parameter->getMallId() === null) {
                $filteredProductParameterValues[] = $productParameterValue;
            }
        }

        return $filteredProductParameterValues;
    }
}
