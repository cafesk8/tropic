<?php

declare(strict_types=1);

namespace App\Component\EntityExtension;

use Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator as BaseEntityManagerDecorator;

class EntityManagerDecorator extends BaseEntityManagerDecorator
{
    /**
     * {@inheritdoc}
     */
    public function createQueryBuilder()
    {
        return new QueryBuilder($this, $this->entityNameResolver);
    }
}
