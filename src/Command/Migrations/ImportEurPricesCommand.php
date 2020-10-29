<?php

declare(strict_types=1);

namespace App\Command\Migrations;

use App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager;
use App\Model\Product\ProductFacade;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportEurPricesCommand extends Command
{
    private const ORDINARY_CUSTOMER_PRICING_GROUP_ID_SK_DOMAIN_PRODUCTION = 5;
    private const REGISTERED_CUSTOMER_PRICING_GROUP_ID_SK_DOMAIN_PRODUCTION = 3;

    /**
     * @var string
     */
    protected static $defaultName = 'shopsys:import:eur-prices';

    private ProductFacade $productFacade;

    /**
     * @var \App\Component\EntityExtension\EntityManagerDecorator
     */
    private EntityManagerInterface $entityManager;

    private PohodaEntityManager $pohodaEntityManager;

    /**
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Component\EntityExtension\EntityManagerDecorator $entityManager
     * @param \App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager $pohodaEntityManager
     */
    public function __construct(
        ProductFacade $productFacade,
        EntityManagerInterface $entityManager,
        PohodaEntityManager $pohodaEntityManager
    ) {
        parent::__construct();
        $this->productFacade = $productFacade;
        $this->entityManager = $entityManager;
        $this->pohodaEntityManager = $pohodaEntityManager;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setDescription('Import EUR prices from Pohoda IS');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $allPohodaIds = $this->productFacade->getPohodaIdsForProductsUpdatedSince(null);
        $progressBar = $this->createProgressBar($output, 2 * count($allPohodaIds));
        $pricingGroupIdsIndexedByExtId = [
            3 => self::ORDINARY_CUSTOMER_PRICING_GROUP_ID_SK_DOMAIN_PRODUCTION,
            4 => self::REGISTERED_CUSTOMER_PRICING_GROUP_ID_SK_DOMAIN_PRODUCTION,
        ];
        $chunkedPohodaIds = array_chunk($allPohodaIds, 2000);
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('RefSkCeny', 'RefSkCeny');
        $resultSetMapping->addScalarResult('ProdejC', 'ProdejC');
        $resultSetMapping->addScalarResult('RefAg', 'RefAg');
        $progressBar->start();
        foreach ($chunkedPohodaIds as $pohodaIds) {
            $productIdsIndexedByPohodaId = $this->productFacade->getProductIdsIndexedByPohodaIds($pohodaIds);
            $pricesData = $this->pohodaEntityManager->createNativeQuery(
                'SELECT RefAg, RefSkCeny, ProdejC 
                FROM SKzCn
                WHERE RefAg IN (:pohodaProductIds)
                AND RefSkCeny IN (3,4)', $resultSetMapping
            )->setParameter('pohodaProductIds', $pohodaIds)->getResult();

            foreach ($pricesData as $priceData) {
                $price = $priceData['ProdejC'];
                $productId = $productIdsIndexedByPohodaId[$priceData['RefAg']]['productId'];
                $pricingGroupId = $pricingGroupIdsIndexedByExtId[$priceData['RefSkCeny']];
                $this->entityManager->createNativeQuery(
                    'INSERT INTO product_manual_input_prices (product_id, pricing_group_id, input_price)
                    VALUES (:productId, :pricingGroupId, :inputPrice)
                    ON CONFLICT (product_id, pricing_group_id) DO UPDATE SET input_price = :inputPrice', new ResultSetMapping()
                )->execute([
                    'pricingGroupId' => $pricingGroupId,
                    'productId' => $productId,
                    'inputPrice' => $price,
                ]);
                $progressBar->advance();
            }
        }
        $progressBar->finish();

        return 0;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param int $max
     * @return \Symfony\Component\Console\Helper\ProgressBar
     */
    private function createProgressBar(OutputInterface $output, int $max): ProgressBar
    {
        $progressBar = new ProgressBar($output, $max);
        $progressBar->setBarCharacter('<fg=magenta>=</>');
        $progressBar->setRedrawFrequency(100);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% ');

        return $progressBar;
    }
}
