<?php

declare(strict_types=1);

namespace App\Model\Customer\User;

use App\Model\Order\Order;
use DateTime;
use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormData;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserRepository as BaseCustomerUserRepository;
use Shopsys\FrameworkBundle\Component\String\DatabaseSearching;
use Shopsys\FrameworkBundle\Model\Customer\BillingAddress;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;

/**
 * @method \App\Model\Customer\User\CustomerUser|null findCustomerUserByEmailAndDomain(string $email, int $domainId)
 * @method \App\Model\Customer\User\CustomerUser|null getUserByEmailAndDomain(string $email, int $domainId)
 * @method \App\Model\Customer\User\CustomerUser getCustomerUserById(int $id)
 * @method \App\Model\Customer\User\CustomerUser|null findById(int $id)
 * @method \App\Model\Customer\User\CustomerUser|null findByIdAndLoginToken(int $id, string $loginToken)
 * @method replaceUsersPricingGroup(\App\Model\Pricing\Group\PricingGroup $oldPricingGroup, \App\Model\Pricing\Group\PricingGroup $newPricingGroup)
 * @method \App\Model\Customer\User\CustomerUser|null getCustomerUserByEmailAndDomain(string $email, int $domainId)
 * @method replaceCustomerUsersPricingGroup(\App\Model\Pricing\Group\PricingGroup $oldPricingGroup, \App\Model\Pricing\Group\PricingGroup $newPricingGroup)
 * @method \App\Model\Customer\User\CustomerUser getOneByUuid(string $uuid)
 */
class CustomerUserRepository extends BaseCustomerUserRepository
{
    /**
     * @param int[] $userIds
     * @return \App\Model\Customer\User\CustomerUser[]
     */
    public function getUsersByIds(array $userIds): array
    {
        return $this->getCustomerUserRepository()->findBy(['id' => $userIds]);
    }

    /**
     * @return \App\Model\Customer\User\CustomerUser[]
     */
    public function getAllUsers(): array
    {
        return $this->getCustomerUserRepository()->findAll();
    }

    /**
     * @param int $limit
     * @return \App\Model\Customer\User\CustomerUser[]
     */
    public function getNotExportedCustomersBatch(int $limit): array
    {
        return $this->createUserQueryBuilder()
            ->andWhere('u.exportStatus = :exportStatus')
            ->setParameter('exportStatus', CustomerUser::EXPORT_NOT_YET)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int $limit
     * @return \App\Model\Customer\User\CustomerUser[]
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
            ->from(CustomerUser::class, 'u');
    }

    /**
     * @param int $domainId
     * @param \Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormData $quickSearchData
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getCustomerUserListQueryBuilderByQuickSearchData(
        $domainId,
        QuickSearchFormData $quickSearchData
    ) {
        $queryBuilder = parent::getCustomerUserListQueryBuilderByQuickSearchData($domainId, $quickSearchData)
        ->select('u.id,
                u.email,
                u.telephone,
                MAX(pg.name) AS pricingGroup,
                MAX(ba.city) city,
                MAX(CONCAT(u.lastName, \' \', u.firstName)) AS name,
                COUNT(o.id) ordersCount,
                SUM(o.totalPriceWithVat) ordersSumPrice,
                MAX(o.createdAt) lastOrderAt',
                'u.exportStatus');

        return $queryBuilder;
    }

    /**
     * @return \App\Model\Customer\User\CustomerUser[]
     */
    public function getForPricingGroupUpdate(): array
    {
        $queryBuilder = $this->createUserQueryBuilder()
            ->where('u.lastLogin > :dateTime')
            ->setParameter('dateTime', new DateTime('-30 days'))
            ->orderBy('u.lastLogin', 'ASC')
            ->addOrderBy('u.id', 'ASC');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param int $legacyId
     * @return \App\Model\Customer\User\CustomerUser|null
     */
    public function findByLegacyId($legacyId): ?CustomerUser
    {
        return $this->getCustomerUserRepository()->findOneBy([
            'legacyId' => $legacyId,
        ]);
    }
}