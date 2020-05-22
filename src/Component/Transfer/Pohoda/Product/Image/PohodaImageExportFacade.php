<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Product\Image;

use App\Component\Transfer\Logger\TransferLoggerFactory;
use App\Component\Transfer\Pohoda\Exception\PohodaInvalidDataException;
use App\Model\Product\Transfer\ProductImageImportCronModule;

class PohodaImageExportFacade
{
    /**
     * @var \App\Component\Transfer\Pohoda\Product\Image\PohodaImageExportRepository
     */
    private $pohodaImageExportRepository;

    /**
     * @var \App\Component\Transfer\Pohoda\Product\Image\PohodaImageDataValidator
     */
    private $pohodaImageDataValidator;

    /**
     * @var \App\Component\Transfer\Logger\TransferLogger
     */
    private $transferLogger;

    /**
     * @param \App\Component\Transfer\Pohoda\Product\Image\PohodaImageExportRepository $pohodaImageExportRepository
     * @param \App\Component\Transfer\Logger\TransferLoggerFactory $transferLoggerFactory
     * @param \App\Component\Transfer\Pohoda\Product\Image\PohodaImageDataValidator $pohodaImageDataValidator
     */
    public function __construct(
        PohodaImageExportRepository $pohodaImageExportRepository,
        TransferLoggerFactory $transferLoggerFactory,
        PohodaImageDataValidator $pohodaImageDataValidator
    ) {
        $this->transferLogger = $transferLoggerFactory->getTransferLoggerByIdentifier(ProductImageImportCronModule::TRANSFER_IDENTIFIER);
        $this->pohodaImageExportRepository = $pohodaImageExportRepository;
        $this->pohodaImageDataValidator = $pohodaImageDataValidator;
    }

    /**
     * @param int[] $productPohodaIds
     * @return \App\Component\Transfer\Pohoda\Product\Image\PohodaImage[]
     */
    public function getPohodaImages(array $productPohodaIds): array
    {
        $pohodaImages = [];
        $pohodaImagesData = $this->pohodaImageExportRepository->getImagesDataFromPohoda($productPohodaIds);
        foreach ($pohodaImagesData as $pohodaImageData) {
            try {
                $this->pohodaImageDataValidator->validate($pohodaImageData);
                $pohodaImages[] = new PohodaImage($pohodaImageData);
            } catch (PohodaInvalidDataException $ex) {
                $this->transferLogger->addError('Položka není validní a nebude přenesena.', [
                    'pohodaId' => $pohodaImageData[PohodaImage::ALIAS_ID],
                    'exceptionMessage' => $ex->getMessage(),
                ]);
            }
        }

        return $pohodaImages;
    }
}
