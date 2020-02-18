<?php

declare(strict_types=1);

namespace App\Model\Administrator;

use Shopsys\FrameworkBundle\Model\Administrator\AdministratorData;
use Shopsys\FrameworkBundle\Model\Administrator\AdministratorDataFactory as BaseAdministratorDataFactory;

/**
 * @method \Shopsys\FrameworkBundle\Model\Administrator\AdministratorData createFromAdministrator(\App\Model\Administrator\Administrator $administrator)
 */
class AdministratorDataFactory extends BaseAdministratorDataFactory
{
    /**
     * @return \Shopsys\FrameworkBundle\Model\Administrator\AdministratorData
     */
    public function create(): AdministratorData
    {
        $administratorData = parent::create();
        $administratorData->roles = array_values(Role::getAllRolesIndexedByTitles());

        return $administratorData;
    }
}
