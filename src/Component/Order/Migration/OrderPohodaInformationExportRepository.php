<?php

declare(strict_types=1);

namespace App\Component\Order\Migration;

use App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager;
use Doctrine\ORM\Query\ResultSetMapping;

class OrderPohodaInformationExportRepository
{
    private PohodaEntityManager $pohodaEntityManager;

    /**
     * @param \App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager $pohodaEntityManager
     */
    public function __construct(PohodaEntityManager $pohodaEntityManager)
    {
        $this->pohodaEntityManager = $pohodaEntityManager;
    }

    /**
     * @param string[] $orderNumbers
     * @return string[]
     */
    public function getOrderNumbersWithPohodaId(array $orderNumbers): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('ID', 'pohodaOrderId');
        $resultSetMapping->addScalarResult('PDoklad', 'orderNumber');

        $query = $this->pohodaEntityManager->createNativeQuery(
            'SELECT O.ID, O.PDoklad
            FROM OBJ O
            WHERE O.PDoklad IN (:orderNumbers)',
            $resultSetMapping
        )
            ->setParameters([
                'orderNumbers' => $orderNumbers,
            ]);

        $orderNumbersByPohodaId = [];
        foreach ($query->getScalarResult() as $pohodaOrderRow) {
            $orderNumbersByPohodaId[(int)$pohodaOrderRow['pohodaOrderId']] = $pohodaOrderRow['orderNumber'];
        }

        return $orderNumbersByPohodaId;
    }
}
