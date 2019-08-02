<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Command\Migration;

use Doctrine\DBAL\Driver\Connection;
use Shopsys\ShopBundle\Command\Migration\Exception\MigrationDataNotFoundException;
use Shopsys\ShopBundle\Component\Image\Exception\MigrateImageToEntityFailedException;
use Shopsys\ShopBundle\Component\Image\ImageFacade;
use Shopsys\ShopBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Product\ProductFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateProductImagesCommand extends Command
{
    private const BATCH_LIMIT = 100;

    /**
     * @var string
     */
    protected static $defaultName = 'shopsys:migrate:product-images';

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \Shopsys\ShopBundle\Component\Image\ImageFacade
     */
    private $imageFacade;

    /**
     * @param \Doctrine\DBAL\Driver\Connection $connection
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     * @param \Shopsys\ShopBundle\Component\Image\ImageFacade $imageFacade
     */
    public function __construct(
        Connection $connection,
        ProductFacade $productFacade,
        ImageFacade $imageFacade
    ) {
        parent::__construct();
        $this->connection = $connection;
        $this->productFacade = $productFacade;
        $this->imageFacade = $imageFacade;
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
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $symfonyStyleIo = new SymfonyStyle($input, $output);

        $page = 0;

        $products = $this->productFacade->getWithEan(self::BATCH_LIMIT, $page);
        $productsCount = count($products);

        while ($productsCount > 0) {
            foreach ($products as $product) {
                $this->imageFacade->deleteImagesFromMigration($product);
                $this->migrateProductImage($product, $symfonyStyleIo);
            }
        }
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param \Symfony\Component\Console\Style\SymfonyStyle $symfonyStyleIo
     */
    private function migrateProductImage(Product $product, SymfonyStyle $symfonyStyleIo)
    {
        $productImagesData = $this->getMigrateProductData($product);

        foreach ($productImagesData as $productImageData) {
            $imageFileName = $productImageData['migrateProductId'] . '/' . $productImageData['migratePhotogalleryId'] . '.jpg';
            try {
                $this->imageFacade->migrateImage($product, $imageFileName, null);
                $symfonyStyleIo->success(sprintf(
                    'Image `%s` for product with EAN `%s` was migrated',
                    $productImageData['migratePhotogalleryId'],
                    $product->getEan()
                ));
            } catch (MigrateImageToEntityFailedException $ex) {
                $symfonyStyleIo->success(sprintf(
                    'Image `%s` for product with EAN `%s` was not migrated, because of error `%s`',
                    $productImageData['migratePhotogalleryId'],
                    $product->getEan(),
                    $ex->getMessage()
                ));
            }
        }
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return string[]
     */
    private function getMigrateProductData(Product $product): array
    {
        $sql = 'SELECT DISTINCT svi.nid AS migrateProductId, svi.fid AS migratePhotogalleryId
            FROM `sklad_varianty` sv
            JOIN `sklad_varianty_image` svi ON sv.var_id = svi.var_id
            WHERE sv.ean = :ean';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('ean', $product->getEan());
        $stmt->execute();

        $migrateProductData = $stmt->fetchAll();
        if (count($migrateProductData) === 0) {
            throw new MigrationDataNotFoundException(sprintf('No data found for product with EAN `%s`', $product->getEan()));
        }
        return $migrateProductData;
    }
}
