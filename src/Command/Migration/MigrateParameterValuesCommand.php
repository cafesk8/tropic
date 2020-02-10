<?php

declare(strict_types=1);

namespace App\Command\Migration;

use App\Command\Migration\Exception\MigrationDataNotFoundException;
use App\Model\Product\Parameter\ParameterFacade;
use App\Model\Product\Parameter\ParameterValue;
use App\Model\Product\Parameter\ParameterValueData;
use App\Model\Product\Parameter\ParameterValueDataFactory;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Shopsys\FrameworkBundle\Component\String\TransformString;
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
     * @var \Doctrine\DBAL\Driver\Connection
     */
    private $connection;

    /**
     * @var \App\Model\Product\Parameter\ParameterFacade
     */
    private $parameterFacade;

    /**
     * @var \App\Model\Product\Parameter\ParameterValueDataFactory
     */
    private $parameterValueDataFactory;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param \Doctrine\DBAL\Driver\Connection $connection
     * @param \App\Model\Product\Parameter\ParameterFacade $parameterFacade
     * @param \App\Model\Product\Parameter\ParameterValueDataFactory $parameterValueDataFactory
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     */
    public function __construct(
        Connection $connection,
        ParameterFacade $parameterFacade,
        ParameterValueDataFactory $parameterValueDataFactory,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();

        $this->connection = $connection;
        $this->parameterFacade = $parameterFacade;
        $this->parameterValueDataFactory = $parameterValueDataFactory;
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
            $parameterValues = $this->parameterFacade->getParameterValuesBatch(self::BATCH_LIMIT, $page);
            $parameterValuesCount = count($parameterValues);
            $page++;
            foreach ($parameterValues as $parameterValue) {
                $this->entityManager->beginTransaction();
                try {
                    $parameterValueData = $this->mapParameterValueData($parameterValue);
                    $this->parameterFacade->editParameterValue($parameterValue, $parameterValueData);

                    $symfonyStyleIo->success(sprintf(
                        'Data for parameter value with ID `%s` was migrated',
                        $parameterValue->getId()
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
        } while ($parameterValuesCount > 0);

        return 0;
    }

    /**
     * @param \App\Model\Product\Parameter\ParameterValue $parameterValue
     * @return \App\Model\Product\Parameter\ParameterValueData|\App\Model\Product\Parameter\ParameterValueData
     */
    private function mapParameterValueData(ParameterValue $parameterValue): ParameterValueData
    {
        $migrateParameterValueData = $this->getMigrateParameterValueData($parameterValue);

        $parameterValueData = $this->parameterValueDataFactory->createFromParameterValue($parameterValue);
        $parameterValueData->hsFeedId = $migrateParameterValueData['hsFeedId'];

        $rbg = TransformString::emptyToNull($migrateParameterValueData['rgb']);
        $parameterValueData->rgb = $rbg !== null ? strip_tags($rbg) : null;

        $parameterValueData->mallName = TransformString::emptyToNull($migrateParameterValueData['mallName']);

        return $parameterValueData;
    }

    /**
     * @param \App\Model\Product\Parameter\ParameterValue $parameterValue
     * @return string[]
     */
    private function getMigrateParameterValueData(ParameterValue $parameterValue): array
    {
        $sql = 'SELECT tid AS hsFeedId, LOWER(description) AS rgb, mallcz_parameter_value_id AS mallName
            FROM `taxonomy_term_data` ttd
            LEFT JOIN `mallcz_parameter_values` mpv ON ttd.tid=mpv.bushman_parameter_value_id
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
