<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190819094840 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('UPDATE parameters p 
            SET type = \'color\' 
            FROM parameter_translations pt 
            WHERE p.id = pt.translatable_id 
            AND pt.name=\'Barva\' 
            AND pt.locale=\'cs\'
        ');

        $this->sql('UPDATE parameters p 
            SET type = \'size\' 
            FROM parameter_translations pt 
            WHERE p.id = pt.translatable_id 
            AND pt.name=\'Velikost\' 
            AND pt.locale=\'cs\'');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
