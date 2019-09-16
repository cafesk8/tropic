<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer;

use DateTime;
use Shopsys\FrameworkBundle\Component\EntityExtension\QueryBuilder;
use Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormData;
use Shopsys\FrameworkBundle\Model\Customer\UserRepository as BaseUserRepository;

class UserRepository extends BaseUserRepository
{
    /**
     * @param int[] $userIds
     * @return \Shopsys\ShopBundle\Model\Customer\User[]
     */
    public function getUsersByIds(array $userIds): array
    {
        return $this->getUserRepository()->findBy(['id' => $userIds]);
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Customer\User[]
     */
    public function getAllUsers(): array
    {
        return $this->getUserRepository()->findAll();
    }

    /**
     * @param int $limit
     * @return \Shopsys\ShopBundle\Model\Customer\User[]
     */
    public function getNotExportedCustomersBatch(int $limit): array
    {
        return $this->createUserQueryBuilder()
            ->andWhere('u.exportStatus = :exportStatus')
            ->setParameter('exportStatus', User::EXPORT_NOT_YET)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int $limit
     * @return \Shopsys\ShopBundle\Model\Customer\User[]
     */
    public function getCustomersWithoutDeliveryAddress(int $limit): array
    {
        return $this->createUserQueryBuilder()
            ->andWhere('u.deliveryAddress IS NULL')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function createUserQueryBuilder(): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u');
    }

    /**
     * @param int $domainId
     * @param \Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormData $quickSearchData
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getCustomerListQueryBuilderByQuickSearchData(
        $domainId,
        QuickSearchFormData $quickSearchData
    ) {
        $queryBuilder = parent::getCustomerListQueryBuilderByQuickSearchData($domainId, $quickSearchData);

        $queryBuilder->addSelect('u.exportStatus');

        return $queryBuilder;
    }

    /**
     * @param string $ean
     * @return bool
     */
    public function eanUsed(string $ean): bool
    {
        $user = $this->getUserRepository()->findOneBy(['ean' => $ean]);

        $eanUsed = false;
        if ($user !== null) {
            $eanUsed = true;
        }

        return $eanUsed;
    }

    /**
     * @param int $limit
     * @return \Shopsys\ShopBundle\Model\Customer\User[]
     */
    public function getBatchToPricingGroupUpdate(int $limit): array
    {
        $queryBuilder = $this->createUserQueryBuilder()
            ->where('u.pricingGroupUpdatedAt < :dateTime')
            ->setParameter('dateTime', new DateTime('-5 minutes'))
            ->orderBy('u.pricingGroupUpdatedAt', 'ASC')
            ->addOrderBy('u.id', 'ASC')
            ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }
}
