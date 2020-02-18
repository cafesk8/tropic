<?php

declare(strict_types=1);

namespace App\Command\Migration;

use App\Command\Migration\Exception\MigrationDataNotFoundException;
use App\Component\Image\Exception\MigrateImageToEntityFailedException;
use App\Component\Image\ImageFacade;
use App\Model\Product\Product;
use App\Model\Product\ProductFacade;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateProductImagesCommand extends Command
{
    private const BATCH_LIMIT = 10;

    /**
     * @var string
     */
    protected static $defaultName = 'shopsys:migrate:product-images';

    /**
     * @var \Doctrine\DBAL\Driver\Connection
     */
    private $connection;

    /**
     * @var \App\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \App\Component\Image\ImageFacade
     */
    private $imageFacade;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param \Doctrine\DBAL\Driver\Connection $connection
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Component\Image\ImageFacade $imageFacade
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     */
    public function __construct(
        Connection $connection,
        ProductFacade $productFacade,
        ImageFacade $imageFacade,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->connection = $connection;
        $this->productFacade = $productFacade;
        $this->imageFacade = $imageFacade;
        $this->entityManager = $entityManager;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Migrate product images');
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
                    $this->imageFacade->deleteImagesFromMigration($product);
                    $this->migrateProductImage($product, $symfonyStyleIo);
                    $this->entityManager->commit();
                } catch (MigrateImageToEntityFailedException | MigrationDataNotFoundException | Exception $exception) {
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
     * @param \Symfony\Component\Console\Style\SymfonyStyle $symfonyStyleIo
     */
    private function migrateProductImage(Product $product, SymfonyStyle $symfonyStyleIo)
    {
        $productImagesData = $this->getMigrateProductData($product);

        foreach ($productImagesData as $productImageData) {
            $imageFileName = $productImageData['migrateProductId'] . '/' . $productImageData['migrateFilename'];
            $this->imageFacade->migrateImage($product, $imageFileName, null);
            $symfonyStyleIo->success(sprintf(
                'Image `%s` for product(%s) with EAN `%s` was migrated',
                $productImageData['migrateFilename'],
                $product->getId(),
                $product->getEan()
            ));
        }
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return string[][]
     */
    private function getMigrateProductData(Product $product): array
    {
        $sql = 'SELECT DISTINCT svi.nid AS migrateProductId, fm.filename AS migrateFilename
            FROM `sklad_varianty` sv
            JOIN `sklad_varianty_image` svi ON sv.var_id = svi.var_id
            JOIN `file_managed` fm ON fm.fid = svi.fid 
            WHERE sv.ean = :ean';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('ean', $product->getEan());
        $stmt->execute();

        $migrateProductData = $stmt->fetchAll();
        if (count($migrateProductData) === 0) {
            throw new MigrationDataNotFoundException(sprintf(
                'No data found for product(%s) with EAN `%s`',
                $product->getId(),
                $product->getEan()
            ));
        }
        return $migrateProductData;
    }
}
