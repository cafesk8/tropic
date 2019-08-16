<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Command\Migration;

use Doctrine\DBAL\Driver\Connection;
use Exception;
use Shopsys\ShopBundle\Command\Migration\Exception\MigrationDataNotFoundException;
use Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade;
use Shopsys\ShopBundle\Model\Product\Parameter\ParameterValue;
use Shopsys\ShopBundle\Model\Product\Parameter\ParameterValueData;
use Shopsys\ShopBundle\Model\Product\Parameter\ParameterValueDataFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateParameterValuesCommand extends Command
{
    private const BATCH_LIMIT = 10;

    /**
     * @var string
     */
    protected static $defaultName = 'shopsys:migrate:parameter-values';

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade
     */
    private $parameterFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Parameter\ParameterValueDataFactory
     */
    private $parameterValueDataFactory;

    /**
     * @param \Doctrine\DBAL\Driver\Connection $connection
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade $parameterFacade
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterValueDataFactory $parameterValueDataFactory
     */
    public function __construct(
        Connection $connection,
        ParameterFacade $parameterFacade,
        ParameterValueDataFactory $parameterValueDataFactory
    ) {
        parent::__construct();

        $this->connection = $connection;
        $this->parameterFacade = $parameterFacade;
        $this->parameterValueDataFactory = $parameterValueDataFactory;
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
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $symfonyStyleIo = new SymfonyStyle($input, $output);

        $page = 0;
        do {
            $parameterValues = $this->parameterFacade->getParameterValuesBatch(self::BATCH_LIMIT, $page);
            $parameterValuesCount = count($parameterValues);
            $page++;
            foreach ($parameterValues as $parameterValue) {
                try {
                    $parameterValueData = $this->mapParameterValueData($parameterValue);
                    $this->parameterFacade->editParameterValue($parameterValue, $parameterValueData);

                    $symfonyStyleIo->success(sprintf(
                        'Data for parameter value with ID `%s` was migrated',
                        $parameterValue->getId()
                    ));
                } catch (MigrationDataNotFoundException | Exception $exception) {
                    $symfonyStyleIo->error($exception->getMessage());
                }
            }
        } while ($parameterValuesCount > 0);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterValue $parameterValue
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValueData|\Shopsys\ShopBundle\Model\Product\Parameter\ParameterValueData
     */
    private function mapParameterValueData(ParameterValue $parameterValue): ParameterValueData
    {
        $migrateParameterValueData = $this->getMigrateParameterValueData($parameterValue);

        $parameterValueData = $this->parameterValueDataFactory->createFromParameterValue($parameterValue);
        $parameterValueData->hsFeedId = $migrateParameterValueData['hsFeedId'];

        return $parameterValueData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $parameterValue
     * @return string[]
     */
    private function getMigrateParameterValueData(ParameterValue $parameterValue): array
    {
        $sql = 'SELECT tid AS hsFeedId
            FROM `taxonomy_term_data` 
            WHERE name = :name';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('name', $parameterValue->getText());
        $stmt->execute();

        $migrateProductData = $stmt->fetchAll();
        if (count($migrateProductData) === 0) {
            throw new MigrationDataNotFoundException(sprintf('No data found for parameter value with ID `%s`', $parameterValue->getId()));
        }
        return $migrateProductData[0];
    }
}
