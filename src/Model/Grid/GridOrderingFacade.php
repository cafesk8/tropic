<?php

declare(strict_types=1);

namespace App\Model\Grid;

use Shopsys\FrameworkBundle\Component\Grid\Ordering\GridOrderingFacade as BaseGridOrderingFacade;

/**
 * @property \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
 */
class GridOrderingFacade extends BaseGridOrderingFacade
{
    /**
     * @inheritDoc
     */
    public function saveOrdering($entityClass, array $rowIds): void
    {
        $entityRepository = $this->getEntityRepository($entityClass);
        $position = 0;

        foreach ($rowIds as $rowId) {
            $entity = $entityRepository->find($rowId);
            $entity->setPosition($position++);
            //copy paste from vendor

            $this->em->flush($entity); //flush with entity because Gedmo\Sortable\SortableListener update position
        }
    }
}
