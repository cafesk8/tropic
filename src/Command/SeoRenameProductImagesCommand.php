<?php

declare(strict_types=1);

namespace App\Command;

use App\Component\Image\Image;
use App\Component\Image\ImageFacade;
use App\Component\Image\ImageLocator;
use App\Model\Product\ProductFacade;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Image\Config\ImageConfig;
use Shopsys\FrameworkBundle\Model\Product\Exception\ProductNotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SeoRenameProductImagesCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'shopsys:seo-rename-product-images';

    protected string $imageDir;

    protected FilesystemInterface $filesystem;

    protected ImageFacade $imageFacade;

    protected ProductFacade $productFacade;

    protected ImageLocator $imageLocator;

    protected Domain $domain;

    /**
     * @param string $imageDir
     * @param \League\Flysystem\FilesystemInterface $filesystem
     * @param \App\Component\Image\ImageFacade $imageFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Component\Image\ImageLocator $imageLocator
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        string $imageDir,
        FilesystemInterface $filesystem,
        ImageFacade $imageFacade,
        ProductFacade $productFacade,
        ImageLocator $imageLocator,
        Domain $domain
    ) {
        parent::__construct();

        $this->imageDir = $imageDir;
        $this->filesystem = $filesystem;
        $this->imageFacade = $imageFacade;
        $this->productFacade = $productFacade;
        $this->imageLocator = $imageLocator;
        $this->domain = $domain;
    }

    protected function configure()
    {
        $this
            ->setDescription('Rename product images to seo optimized')
            ->addArgument('imageIdFrom', InputArgument::OPTIONAL, 'Id image from begin');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<fg=green>Renaming product images...</fg=green>');

        $imageIdFrom = $input->getArgument('imageIdFrom') ? (int)$input->getArgument('imageIdFrom') : null;

        $this->renameProductImages($output, $imageIdFrom);

        $output->writeln('<fg=green>Product images successfully renamed</fg=green>');

        return 0;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param int|null $imageIdFrom
     */
    private function renameProductImages(OutputInterface $output, ?int $imageIdFrom = null): void
    {
        $imagesToRename = $this->imageFacade->getImagesByEntityNameAndTypeOrderedById('product', null, $imageIdFrom);
        foreach ($imagesToRename as $imageToRename) {
            $oldImagePath = $this->imageLocator->getRelativeImagePath($imageToRename->getEntityName(), $imageToRename->getType(), ImageConfig::ORIGINAL_SIZE_NAME);
            $oldImageFilepath = $this->imageDir . $oldImagePath . $imageToRename->getFilename();

            if ($this->filesystem->has($oldImageFilepath) === false) {
                $output->writeln(sprintf('<fg=red>Image with ID=%d not exist in path=%s, skipping</fg=red>', $imageToRename->getId(), $oldImageFilepath));

                continue;
            }

            try {
                $newImagesPath = $this->getNewImagePath($imageToRename);
            } catch (ProductNotFoundException $e) {
                $output->writeln('<fg=red>' . $e->getMessage() . ', delete old image and skip.</fg=red>');
                $this->filesystem->delete($oldImageFilepath);

                continue;
            }

            $output->writeln(json_encode($newImagesPath));

            foreach ($newImagesPath as $newImagePath) {
                $output->writeln('<fg=green>' . $newImagePath . '</fg=green>');
                try {
                    $this->filesystem->copy($oldImageFilepath, $newImagePath);
                } catch (FileExistsException | FileNotFoundException $e) {
                    $output->writeln('<fg=red>' . $e->getMessage() . '</fg=red>');
                }
            }

            $this->filesystem->delete($oldImageFilepath);
        }
    }

    /**
     * @param \App\Component\Image\Image $image
     * @return array
     */
    private function getNewImagePath(Image $image): array
    {
        $newImagesPath = [];
        $allLocales = $this->domain->getAllLocales();
        foreach ($allLocales as $locale) {
            $newImagesPath[] = $this->imageLocator->getAbsoluteImageFilepath($image, ImageConfig::ORIGINAL_SIZE_NAME, $locale);
        }

        return $newImagesPath;
    }
}
