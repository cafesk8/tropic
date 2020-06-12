<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Product\Image;

use App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager;
use Doctrine\ORM\Query\ResultSetMapping;

class PohodaImageExportRepository
{
    /**
     * @var \App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager
     */
    private $pohodaEntityManager;

    /**
     * @param \App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager $pohodaEntityManager
     */
    public function __construct(PohodaEntityManager $pohodaEntityManager)
    {
        $this->pohodaEntityManager = $pohodaEntityManager;
    }

    /**
     * @param int[] $productPohodaIds
     * @return array
     */
    public function getImagesDataFromPohoda(array $productPohodaIds): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping
            ->addScalarResult('ID', PohodaImage::ALIAS_ID)
            ->addScalarResult('Vychozi', PohodaImage::ALIAS_DEFAULT)
            ->addScalarResult('RefAg', PohodaImage::ALIAS_PRODUCT_POHODA_ID)
            ->addScalarResult('Soubor', PohodaImage::ALIAS_FILE)
            ->addScalarResult('OrderFld', PohodaImage::ALIAS_POSITION);

        $query = $this->pohodaEntityManager
            ->createNativeQuery(
                'SELECT
            img.ID,
            img.Vychozi,
            img.RefAg,
            img.Soubor,
            img.OrderFld
            FROM
            SkRefObraz img
            WHERE img.RefAg IN (:productPohodaIds)            
        ',
                $resultSetMapping
            )->setParameter('productPohodaIds', $productPohodaIds);

        return $query->getResult();
    }
}
