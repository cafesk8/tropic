<?php

declare(strict_types=1);

namespace App\Model\Order\GiftCertificate\Pdf;

use App\Component\FileUpload\FileUpload;
use App\Model\Order\GiftCertificate\OrderGiftCertificate;
use Dompdf\Dompdf;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use Shopsys\FrameworkBundle\Component\UploadedFile\UploadedFileDataFactory;
use Shopsys\FrameworkBundle\Component\UploadedFile\UploadedFileFacade;
use Twig\Environment;

class OrderGiftCertificatePdfFacade
{
    private Environment $twigEnvironment;

    private UploadedFileFacade $uploadedFileFacade;

    private FileUpload $fileUpload;

    private UploadedFileDataFactory $uploadedFileDataFactory;

    private Filesystem $filesystem;

    /**
     * @param \Twig\Environment $twigEnvironment
     * @param \Shopsys\FrameworkBundle\Component\UploadedFile\UploadedFileFacade $uploadedFileFacade
     * @param \App\Component\FileUpload\FileUpload $fileUpload
     * @param \Shopsys\FrameworkBundle\Component\UploadedFile\UploadedFileDataFactory $uploadedFileDataFactory
     * @param \League\Flysystem\Filesystem $filesystem
     */
    public function __construct(
        Environment $twigEnvironment,
        UploadedFileFacade $uploadedFileFacade,
        FileUpload $fileUpload,
        UploadedFileDataFactory $uploadedFileDataFactory,
        FilesystemInterface $filesystem
    ) {
        $this->twigEnvironment = $twigEnvironment;
        $this->uploadedFileFacade = $uploadedFileFacade;
        $this->fileUpload = $fileUpload;
        $this->uploadedFileDataFactory = $uploadedFileDataFactory;
        $this->filesystem = $filesystem;
    }

    /**
     * @param \App\Model\Order\GiftCertificate\OrderGiftCertificate $orderGiftCertificate
     */
    public function create(OrderGiftCertificate $orderGiftCertificate): void
    {
        $dirName = $this->fileUpload->getTemporaryDirectory();

        if (!is_dir($dirName)) {
            mkdir($dirName, 0777, true);
        }

        $uploadedFileData = $this->uploadedFileDataFactory->createByEntity($orderGiftCertificate);
        $randomHash = substr(md5((string)random_int(0, 1000000)), 0, 4);

        $fileName = t('Darkovy_poukaz_-_barevny') . '_' . $randomHash . '.pdf';
        $this->filesystem->put($dirName . '/' . $fileName, $this->renderPdf($orderGiftCertificate, false)->output());
        $fileNames = [$fileName];

        $fileName = t('Darkovy_poukaz_-_cernobily') . '_' . $randomHash . '.pdf';
        $this->filesystem->put($dirName . '/' . $fileName, $this->renderPdf($orderGiftCertificate, true)->output());
        $fileNames[] = $fileName;
        $uploadedFileData->uploadedFiles = $fileNames;
        $uploadedFileData->uploadedFilenames = $fileNames;

        $this->uploadedFileFacade->manageFiles($orderGiftCertificate, $uploadedFileData);

        try {
            foreach ($fileNames as $fileName) {
                $this->filesystem->delete($dirName . '/' . $fileName);
            }
        } catch (FileNotFoundException $fileNotFoundException) {}
    }

    /**
     * @param \App\Model\Order\GiftCertificate\OrderGiftCertificate $orderGiftCertificate
     * @param bool $greyscale
     * @return \Dompdf\Dompdf
     */
    private function renderPdf(OrderGiftCertificate $orderGiftCertificate, bool $greyscale): Dompdf
    {
        $dompdf = new Dompdf();
        $giftCertificate = $orderGiftCertificate->getGiftCertificate();
        $html = $this->twigEnvironment->render('Mail/Order/GiftCertificate/giftCertificate.html.twig', [
            'giftCertificateCode' => $giftCertificate->getCode(),
            'giftCertificateCurrency' => $orderGiftCertificate->getOrder()->getCurrency(),
            'giftCertificateValue' => $giftCertificate->getCertificateValue(),
            'giftCertificateValidTo' => $giftCertificate->isActive() ? $giftCertificate->getValidTo() : null,
            'greyscale' => $greyscale,
        ]);
        $dompdf->loadHtml($html);
        $dompdf->render();

        return $dompdf;
    }

    /**
     * @param \App\Model\Order\GiftCertificate\OrderGiftCertificate $orderGiftCertificate
     * @return \Shopsys\FrameworkBundle\Component\UploadedFile\UploadedFile[]
     */
    public function getFiles(OrderGiftCertificate $orderGiftCertificate): array
    {
        return $this->uploadedFileFacade->getUploadedFilesByEntity($orderGiftCertificate);
    }

    /**
     * @param \App\Model\Order\GiftCertificate\OrderGiftCertificate $orderGiftCertificate
     */
    public function delete(OrderGiftCertificate $orderGiftCertificate): void
    {
        $this->uploadedFileFacade->deleteAllUploadedFilesByEntity($orderGiftCertificate);
    }
}
