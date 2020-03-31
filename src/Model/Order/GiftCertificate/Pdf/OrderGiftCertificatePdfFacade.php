<?php

declare(strict_types=1);

namespace App\Model\Order\GiftCertificate\Pdf;

use App\Component\FileUpload\FileUpload;
use App\Model\Order\GiftCertificate\OrderGiftCertificate;
use Dompdf\Dompdf;
use Shopsys\FrameworkBundle\Component\UploadedFile\UploadedFileDataFactory;
use Shopsys\FrameworkBundle\Component\UploadedFile\UploadedFileFacade;
use Symfony\Bridge\Twig\TwigEngine;

class OrderGiftCertificatePdfFacade
{
    /**
     * @var \Symfony\Bridge\Twig\TwigEngine
     */
    private $templating;

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
     * @param \Symfony\Bridge\Twig\TwigEngine $templating
     * @param \Shopsys\FrameworkBundle\Component\UploadedFile\UploadedFileFacade $uploadedFileFacade
     * @param \App\Component\FileUpload\FileUpload $fileUpload
     * @param \Shopsys\FrameworkBundle\Component\UploadedFile\UploadedFileDataFactory $uploadedFileDataFactory
     */
    public function __construct(TwigEngine $templating, UploadedFileFacade $uploadedFileFacade, FileUpload $fileUpload, UploadedFileDataFactory $uploadedFileDataFactory)
    {
        $this->templating = $templating;
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
        $html = $this->templating->render('Mail/Order/GiftCertificate/giftCertificate.html.twig', [
            'giftCertificateCode' => $orderGiftCertificate->getGiftCertificate()->getCode(),
            'giftCertificateCurrency' => $orderGiftCertificate->getOrder()->getCurrency(),
            'giftCertificateValue' => $orderGiftCertificate->getGiftCertificate()->getCertificateValue(),
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
}
