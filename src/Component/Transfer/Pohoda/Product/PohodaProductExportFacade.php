<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Product;

use App\Component\Transfer\Pohoda\Exception\PohodaInvalidDataExceptionInterface;
use DateTime;
use Symfony\Bridge\Monolog\Logger;

class PohodaProductExportFacade
{
    /**
     * @var \App\Component\Transfer\Pohoda\Product\PohodaProductExportRepository
     */
    private $pohodaProductExportRepository;

    /**
     * @var \App\Component\Transfer\Pohoda\Product\PohodaProductDataValidator
     */
    private $pohodaProductDataValidator;

    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $logger;

    /**
     * @param \Symfony\Bridge\Monolog\Logger $logger
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProductExportRepository $pohodaProductExportRepository
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProductDataValidator $pohodaProductDataValidator
     */
    public function __construct(
        Logger $logger,
        PohodaProductExportRepository $pohodaProductExportRepository,
        PohodaProductDataValidator $pohodaProductDataValidator
    ) {
        $this->logger = $logger;
        $this->pohodaProductExportRepository = $pohodaProductExportRepository;
        $this->pohodaProductDataValidator = $pohodaProductDataValidator;
    }

    /**
     * @param \DateTime|null $lastModificationDate
     * @return \App\Component\Transfer\Pohoda\Product\PohodaProduct[]
     */
    public function findPohodaProductIdsFromLastModificationDate(?DateTime $lastModificationDate): array
    {
        return $this->pohodaProductExportRepository->findProductPohodaIdsByLastUpdateTime($lastModificationDate);
    }

    /**
     * @param array $pohodaProductIds
     * @return \App\Component\Transfer\Pohoda\Product\PohodaProduct[]
     */
    public function findPohodaProductsByPohodaIds(array $pohodaProductIds): array
    {
        $pohodaProductsResult = $this->pohodaProductExportRepository->findByPohodaProductIds(
            $pohodaProductIds
        );

        return $this->getValidPohodaProducts($pohodaProductsResult);
    }

    /**
     * @param array $pohodaProductsData
     * @return \App\Component\Transfer\Pohoda\Product\PohodaProduct[]
     */
    private function getValidPohodaProducts(array $pohodaProductsData): array
    {
        $pohodaProducts = [];
        foreach ($pohodaProductsData as $pohodaProductData) {
            try {
                $this->pohodaProductDataValidator->validate($pohodaProductData);
            } catch (PohodaInvalidDataExceptionInterface $exc) {
                $this->logger->addError('Položka s catnum:' . $pohodaProductData[PohodaProduct::COL_CATNUM] . ' s názvem:' . $pohodaProductData[PohodaProduct::COL_NAME] . ' není validní a nebude přenesena - ' . $exc->getMessage());
                continue;
            }

            $pohodaProducts[] = new PohodaProduct($pohodaProductData);
        }

        return $pohodaProducts;
    }
}
