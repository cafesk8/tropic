<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190731134183 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        // passwords are the same
        // admin123

        $this->sql('UPDATE administrators SET password = \'$2y$12$fjT10HImge5/tlFeqgx.eeVVVDwhymTnJ6/L8D0dtnIfUbNVkfWyW\' WHERE id IN (1,2)');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
