<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Command\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Shopsys\ShopBundle\Command\Migration\Exception\MigrationDataNotFoundException;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Product\ProductData;
use Shopsys\ShopBundle\Model\Product\ProductDataFactory;
use Shopsys\ShopBundle\Model\Product\ProductFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateProductDataCommand extends Command
{
    private const BATCH_LIMIT = 10;

    /**
     * @var string
     */
    protected static $defaultName = 'shopsys:migrate:product-data';

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductDataFactory
     */
    private $productDataFactory;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param \Doctrine\DBAL\Connection $connection
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     * @param \Shopsys\ShopBundle\Model\Product\ProductDataFactory $productDataFactory
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     */
    public function __construct(
        Connection $connection,
        ProductFacade $productFacade,
        ProductDataFactory $productDataFactory,
        EntityManagerInterface $entityManager
    ) {
        $this->connection = $connection;
        $this->productFacade = $productFacade;

        parent::__construct();
        $this->productDataFactory = $productDataFactory;
        $this->entityManager = $entityManager;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Migrate product data');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
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
                    $productData = $this->mapProductData($product);
                    $this->productFacade->edit($product->getId(), $productData);

                    $symfonyStyleIo->success(sprintf('Data for product with EAN `%s` was migrated', $product->getEan()));
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
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return \Shopsys\ShopBundle\Model\Product\ProductData
     */
    private function mapProductData(Product $product): ProductData
    {
        $productData = $this->productDataFactory->createFromProduct($product);

        $migrateProductData = $this->getMigrateProductData($product);

        $productData->name = [
            DomainHelper::CZECH_LOCALE => $productData->name[DomainHelper::CZECH_LOCALE],
            DomainHelper::SLOVAK_LOCALE => $migrateProductData['nameSk'],
            DomainHelper::GERMAN_LOCALE => $migrateProductData['nameDe'],
        ];

        $productData->descriptions = [
            DomainHelper::CZECH_DOMAIN => $migrateProductData['descriptionCs'],
            DomainHelper::SLOVAK_DOMAIN => $migrateProductData['descriptionSk'],
            DomainHelper::GERMAN_DOMAIN => $migrateProductData['descriptionDe'],
        ];

        $productData->shortDescriptions = [
            DomainHelper::CZECH_DOMAIN => $this->getFilteredShortDescription($migrateProductData['shortDescriptionCs']),
            DomainHelper::SLOVAK_DOMAIN => $this->getFilteredShortDescription($migrateProductData['shortDescriptionSk']),
            DomainHelper::GERMAN_DOMAIN => $this->getFilteredShortDescription($migrateProductData['shortDescriptionDe']),
        ];

        return $productData;
    }

    /**
     * @param string $shortDescription|null
     * @return string|null
     */
    private function getFilteredShortDescription(?string $shortDescription): ?string
    {
        if ($shortDescription === null) {
            return null;
        }

        $shortDescription = str_replace('<li>', '', $shortDescription);
        $shortDescription = str_replace('</li>', ', ', $shortDescription);

        $shortDescription = strip_tags($shortDescription);

        $shortDescription = trim($shortDescription);
        $shortDescription = rtrim($shortDescription, ',');

        $shortDescription = str_replace("\t", '', $shortDescription);
        $shortDescription = str_replace("\n", '', $shortDescription);
        $shortDescription = str_replace("\r", '', $shortDescription);

        $shortDescription = str_replace('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', '', $shortDescription);

        return $shortDescription;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return string[]
     */
    private function getMigrateProductData(Product $product): array
    {
        $sql = 'SELECT 
                fdfns.field_nazev_sk_value AS nameSk, 
                fdfmd.field_nazev_de_value AS nameDe, 
                frfdpc.field_dlouhy_popis_czlpk_value AS descriptionCs, 
                frfdps.field_dlouhy_popis_sk_value AS descriptionSk, 
                frfdpd.field_dlouhy_popis_de_value AS descriptionDe, 
                frfkpc.field_kratky_popis_czlpk_value AS shortDescriptionCs, 
                frfkps.field_kratky_popis_sk_value AS shortDescriptionSk, 
                frfkpd.field_kratky_popis_de_value AS shortDescriptionDe 
            FROM `sklad_varianty` sv 
            LEFT JOIN `field_data_field_nazev_sk` fdfns ON fdfns.entity_id = sv.nid 
            LEFT JOIN `field_data_field_nazev_de` fdfmd ON fdfmd.entity_id = sv.nid
            LEFT JOIN `field_data_field_dlouhy_popis_czlpk` frfdpc ON frfdpc.entity_id = sv.nid
            LEFT JOIN `field_data_field_dlouhy_popis_sk` frfdps ON frfdps.entity_id = sv.nid
            LEFT JOIN `field_data_field_dlouhy_popis_de` frfdpd ON frfdpd.entity_id = sv.nid
            LEFT JOIN `field_data_field_kratky_popis_czlpk` frfkpc ON frfkpc.entity_id = sv.nid
            LEFT JOIN `field_data_field_kratky_popis_sk` frfkps ON frfkps.entity_id = sv.nid
            LEFT JOIN `field_data_field_kratky_popis_de` frfkpd ON frfkpd.entity_id = sv.nid
            WHERE sv.ean = :ean';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('ean', $product->getEan());
        $stmt->execute();

        $migrateProductData = $stmt->fetchAll();
        if (count($migrateProductData) === 0) {
            throw new MigrationDataNotFoundException(sprintf('No data found for product with EAN `%s`', $product->getEan()));
        }
        return $migrateProductData[0];
    }
}
