<?php

declare(strict_types=1);

namespace App\Model\Customer\User;

use DateTime;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormData;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserRepository as BaseCustomerUserRepository;
use App\Model\Customer\TransferIds\UserTransferId;

/**
 * @method \App\Model\Customer\User\CustomerUser|null findCustomerUserByEmailAndDomain(string $email, int $domainId)
 * @method \App\Model\Customer\User\CustomerUser|null getUserByEmailAndDomain(string $email, int $domainId)
 * @method \App\Model\Customer\User\CustomerUser getCustomerUserById(int $id)
 * @method \App\Model\Customer\User\CustomerUser|null findById(int $id)
 * @method \App\Model\Customer\User\CustomerUser|null findByIdAndLoginToken(int $id, string $loginToken)
 * @method replaceUsersPricingGroup(\App\Model\Pricing\Group\PricingGroup $oldPricingGroup, \App\Model\Pricing\Group\PricingGroup $newPricingGroup)
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
        $queryBuilder = parent::getCustomerUserListQueryBuilderByQuickSearchData($domainId, $quickSearchData);

        if ($quickSearchData->text !== null && $quickSearchData->text !== '') {
            $queryBuilder->leftJoin(UserTransferId::class, 'uti', Join::WITH, 'uti.customer = u')
                ->orWhere('NORMALIZE(uti.ean) LIKE NORMALIZE(:text)')
                ->orWhere('NORMALIZE(u.ean) LIKE NORMALIZE(:text)');
        }

        $queryBuilder->addSelect('u.exportStatus');

        return $queryBuilder;
    }

    /**
     * @param string $ean
     * @return bool
     */
    public function eanUsed(string $ean): bool
    {
        $customerUser = $this->getCustomerUserRepository()->findOneBy(['ean' => $ean]);

        $eanUsed = false;
        if ($customerUser !== null) {
            $eanUsed = true;
        }

        return $eanUsed;
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
}
