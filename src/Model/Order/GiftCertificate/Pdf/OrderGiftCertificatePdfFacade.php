<?php

declare(strict_types=1);

namespace App\Model\Order\GiftCertificate\Pdf;

use App\Component\FileUpload\FileUpload;
use App\Model\Order\GiftCertificate\OrderGiftCertificate;
use Dompdf\Dompdf;
use Shopsys\FrameworkBundle\Component\UploadedFile\UploadedFileDataFactory;
use Shopsys\FrameworkBundle\Component\UploadedFile\UploadedFileFacade;
use Twig\Environment;

class OrderGiftCertificatePdfFacade
{
    /**
     * @var \Twig\Environment
     */
    private $twigEnvironment;

    /**
     * @var \Shopsys\FrameworkBundle\Component\UploadedFile\UploadedFileFacade
     */
    private $uploadedFileFacade;

    /**
     * @var \App\Component\FileUpload\FileUpload
     */
    private $fileUpload;

    /**
     * @var \Shopsys\FrameworkBundle\Component\UploadedFile\UploadedFileDataFactory
     */
    private $uploadedFileDataFactory;

    /**
     * @param \Twig\Environment $twigEnvironment
     * @param \Shopsys\FrameworkBundle\Component\UploadedFile\UploadedFileFacade $uploadedFileFacade
     * @param \App\Component\FileUpload\FileUpload $fileUpload
     * @param \Shopsys\FrameworkBundle\Component\UploadedFile\UploadedFileDataFactory $uploadedFileDataFactory
     */
    public function __construct(Environment $twigEnvironment, UploadedFileFacade $uploadedFileFacade, FileUpload $fileUpload, UploadedFileDataFactory $uploadedFileDataFactory)
    {
        $this->twigEnvironment = $twigEnvironment;
        $this->uploadedFileFacade = $uploadedFileFacade;
        $this->fileUpload = $fileUpload;
        $this->uploadedFileDataFactory = $uploadedFileDataFactory;
    }

    /**
     * @param \App\Model\Order\GiftCertificate\OrderGiftCertificate $orderGiftCertificate
     */
    public function create(OrderGiftCertificate $orderGiftCertificate): void
    {
        $dompdf = new Dompdf();
        $giftCertificate = $orderGiftCertificate->getGiftCertificate();
        $html = $this->twigEnvironment->render('Mail/Order/GiftCertificate/giftCertificate.html.twig', [
            'giftCertificateCode' => $giftCertificate->getCode(),
            'giftCertificateCurrency' => $orderGiftCertificate->getOrder()->getCurrency(),
            'giftCertificateValue' => $giftCertificate->getCertificateValue(),
            'giftCertificateValidTo' => $giftCertificate->isActive() ? $giftCertificate->getValidTo() : null,
        ]);
        $dompdf->loadHtml($html);
        $dompdf->render();

        $dirName = $this->fileUpload->getTemporaryDirectory();

        if (!is_dir($dirName)) {
            mkdir($dirName, 0777, true);
        }

        $fileName = 'GiftCertificate' . strval(time()) . substr(hash('sha256', $orderGiftCertificate->getGiftCertificate()->getCode()), 0, 8) . '.pdf';
        file_put_contents($dirName . '/' . $fileName, $dompdf->output());
        $uploadedFileData = $this->uploadedFileDataFactory->createByEntity($orderGiftCertificate);
        $uploadedFileData->uploadedFiles[] = $fileName;
        $uploadedFileData->uploadedFilenames[] = $fileName;
        $this->uploadedFileFacade->manageFiles($orderGiftCertificate, $uploadedFileData);
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
