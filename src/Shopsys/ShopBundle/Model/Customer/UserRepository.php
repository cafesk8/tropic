<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer;

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
}
