<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use League\Flysystem\FilesystemInterface;
use Shopsys\FrameworkBundle\Component\Grid\GridFactory;
use Shopsys\FrameworkBundle\Component\Router\LocalizedRouterFactory;
use Shopsys\FrameworkBundle\Controller\Admin\SuperadminController as BaseSuperadminController;
use Shopsys\FrameworkBundle\Model\Localization\Localization;
use Shopsys\FrameworkBundle\Model\Module\ModuleFacade;
use Shopsys\FrameworkBundle\Model\Module\ModuleList;
use Shopsys\FrameworkBundle\Model\Pricing\DelayedPricingSetting;
use Shopsys\FrameworkBundle\Model\Pricing\PricingSetting;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SuperadminController extends BaseSuperadminController
{
    /**
     * @var string
     */
    private $shopsysMigrationsDirPath;

    /**
     * @var \League\Flysystem\FilesystemInterface
     */
    private $filesystem;

    /**
     * @param string $shopsysMigrationsDirPath
     * @param \Shopsys\FrameworkBundle\Model\Module\ModuleList $moduleList
     * @param \Shopsys\FrameworkBundle\Model\Module\ModuleFacade $moduleFacade
     * @param \Shopsys\FrameworkBundle\Model\Pricing\PricingSetting $pricingSetting
     * @param \Shopsys\FrameworkBundle\Model\Pricing\DelayedPricingSetting $delayedPricingSetting
     * @param \Shopsys\FrameworkBundle\Component\Grid\GridFactory $gridFactory
     * @param \Shopsys\FrameworkBundle\Model\Localization\Localization $localization
     * @param \Shopsys\FrameworkBundle\Component\Router\LocalizedRouterFactory $localizedRouterFactory
     * @param \League\Flysystem\FilesystemInterface $filesystem
     */
    public function __construct(
        string $shopsysMigrationsDirPath,
        ModuleList $moduleList,
        ModuleFacade $moduleFacade,
        PricingSetting $pricingSetting,
        DelayedPricingSetting $delayedPricingSetting,
        GridFactory $gridFactory,
        Localization $localization,
        LocalizedRouterFactory $localizedRouterFactory,
        FilesystemInterface $filesystem
    ) {
        parent::__construct(
            $moduleList,
            $moduleFacade,
            $pricingSetting,
            $delayedPricingSetting,
            $gridFactory,
            $localization,
            $localizedRouterFactory
        );

        $this->shopsysMigrationsDirPath = $shopsysMigrationsDirPath;
        $this->filesystem = $filesystem;
    }

    /**
     * @Route("/superadmin/migrations/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function migrationsAction(Request $request): Response
    {
        $simpleImportForm = $this->createFormBuilder()
            ->add('uploaded_csv_file', FileType::class, [
                'label' => t('Migrační CSV soubor'),
                'mapped' => false,
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => t('Nahrát soubor'),
            ])
            ->add('delete', SubmitType::class, [
                'label' => t('Smazat všechny soubory'),
            ])
            ->getForm();

        $simpleImportForm->handleRequest($request);

        if ($simpleImportForm->isSubmitted() && $simpleImportForm->isValid()) {

            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $uploadedCsvFile */
            $uploadedCsvFile = $simpleImportForm->get('uploaded_csv_file')->getData();

            /** @var \Symfony\Component\Form\SubmitButton $saveSubmitButton */
            $saveSubmitButton = $simpleImportForm->get('save');

            /** @var \Symfony\Component\Form\SubmitButton $deleteSubmitButton */
            $deleteSubmitButton = $simpleImportForm->get('delete');

            if ($saveSubmitButton->isClicked() && $uploadedCsvFile !== null) {
                $uploadedCsvFile->move($this->shopsysMigrationsDirPath, $uploadedCsvFile->getClientOriginalName());
            }

            if ($deleteSubmitButton->isClicked()) {
                $this->deleteUploadedFiles();
            }
        }

        return $this->render('Admin/Content/Superadmin/migrations.html.twig', [
            'form' => $simpleImportForm->createView(),
            'uploadedFiles' => $this->getUploadedFiles(),
        ]);
    }

    /**
     * @return array
     */
    private function getUploadedFiles(): array
    {
        $finder = new Finder();
        $finder->files()->in($this->shopsysMigrationsDirPath);

        $uploadedFiles = [];
        foreach ($finder as $file) {
            $uploadedFiles[] = [
                'name' => $file->getRelativePathname(),
                'size' => round($file->getSize() / 1048576, 2) . 'MB',
                'last_updated' => date('H:i:s d.m.Y', $file->getMTime()),
            ];
        }

        return $uploadedFiles;
    }

    private function deleteUploadedFiles(): void
    {
        $finder = new Finder();
        $finder->files()->in($this->shopsysMigrationsDirPath);

        foreach ($finder as $file) {
            $localFilePath = 'web/content/uploadedFiles/migrations/' . $file->getRelativePathname();

            if ($this->filesystem->has($localFilePath)) {
                $this->filesystem->delete($localFilePath);
            }
        }
    }
}
