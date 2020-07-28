<?php

declare(strict_types=1);

namespace App\Component\EntityExtension;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder as BaseQueryBuilder;
use Shopsys\FrameworkBundle\Component\EntityExtension\EntityNameResolver;

class QueryBuilder extends BaseQueryBuilder
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityNameResolver
     */
    private $entityNameResolver;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\EntityNameResolver $entityNameResolver
     */
    public function __construct(EntityManagerInterface $em, EntityNameResolver $entityNameResolver)
    {
        parent::__construct($em);

        $this->entityNameResolver = $entityNameResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function getDQL()
    {
        return $this->entityNameResolver->resolveIn(parent::getDQL());
    }
}
