<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Category;

use App\Component\Transfer\Pohoda\Exception\PohodaInvalidDataException;
use DateTime;
use Symfony\Bridge\Monolog\Logger;

class PohodaCategoryExportFacade
{
    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $logger;

    /**
     * @var \App\Component\Transfer\Pohoda\Category\PohodaCategoryDataValidator
     */
    private $pohodaCategoryDataValidator;

    /**
     * @var \App\Component\Transfer\Pohoda\Category\PohodaCategoryExportRepository
     */
    private $pohodaCategoryExportRepository;

    /**
     * @param \Symfony\Bridge\Monolog\Logger $logger
     * @param \App\Component\Transfer\Pohoda\Category\PohodaCategoryExportRepository $pohodaCategoryExportRepository
     * @param \App\Component\Transfer\Pohoda\Category\PohodaCategoryDataValidator $pohodaCategoryDataValidator
     */
    public function __construct(
        Logger $logger,
        PohodaCategoryExportRepository $pohodaCategoryExportRepository,
        PohodaCategoryDataValidator $pohodaCategoryDataValidator
    ) {
        $this->logger = $logger;
        $this->pohodaCategoryExportRepository = $pohodaCategoryExportRepository;
        $this->pohodaCategoryDataValidator = $pohodaCategoryDataValidator;
    }

    /**
     * @param \DateTime|null $lastModificationDate
     * @return \App\Component\Transfer\Pohoda\Product\PohodaProduct[]
     */
    public function getPohodaCategoryIdsByLastUpdateTime(?DateTime $lastModificationDate): array
    {
        return $this->pohodaCategoryExportRepository->getPohodaCategoryIdsByLastUpdateTime($lastModificationDate);
    }

    /**
     * @param int[] $pohodaCategoryIds
     * @return \App\Component\Transfer\Pohoda\Category\PohodaCategory[]
     */
    public function getPohodaCategoriesByPohodaCategoryIds(array $pohodaCategoryIds): array
    {
        $pohodaCategoryResult = $this->pohodaCategoryExportRepository->getByPohodaCategoryIds($pohodaCategoryIds);

        return $this->getValidPohodaCategories($pohodaCategoryResult);
    }

    /**
     * @return array
     */
    public function getAllPohodaIds(): array
    {
        return $this->pohodaCategoryExportRepository->getAllPohodaIds();
    }

    /**
     * @param array $pohodaCategoriesData
     * @return \App\Component\Transfer\Pohoda\Category\PohodaCategory[]
     */
    private function getValidPohodaCategories(array $pohodaCategoriesData): array
    {
        $pohodaCategories = [];
        foreach ($pohodaCategoriesData as $pohodaCategoryData) {
            try {
                $this->pohodaCategoryDataValidator->validate($pohodaCategoryData);
            } catch (PohodaInvalidDataException $exc) {
                $this->logger->addError('Položka není validní a nebude přenesena.', [
                    'pohodaId' => $pohodaCategoryData[PohodaCategory::COL_POHODA_ID],
                    'exceptionMessage' => $exc->getMessage(),
                ]);
                continue;
            }

            $pohodaCategories[] = new PohodaCategory($pohodaCategoryData);
        }

        return $pohodaCategories;
    }
}