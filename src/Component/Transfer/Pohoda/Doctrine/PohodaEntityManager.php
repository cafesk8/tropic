<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Doctrine;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Bridge\Doctrine\ContainerAwareEventManager;

class PohodaEntityManager extends EntityManagerDecorator
{
    /**
     * @param mixed $conn an array with the connection parameters or an existing Connection instance
     * @param \Doctrine\ORM\Configuration $config the Configuration instance to use
     * @param \Symfony\Bridge\Doctrine\ContainerAwareEventManager $eventManager the EventManager instance to use
     *
     * @return \App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager the created EntityManager
     */
    public static function create($conn, Configuration $config, ?ContainerAwareEventManager $eventManager = null): self
    {
        return new self(EntityManager::create($conn, $config, $eventManager));
    }

    /**
     * @return \DateTime
     */
    public function getCurrentDateTimeFromPohodaDatabase(): \DateTime
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('datetime', 'datetime');
        $query = $this->createNativeQuery('SELECT GETDATE() as datetime', $rsm);
        $dateTimeStringWithMicroSeconds = $query->getSingleScalarResult();

        return new \DateTime($dateTimeStringWithMicroSeconds);
    }
}
