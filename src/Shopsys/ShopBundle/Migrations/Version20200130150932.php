<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200130150932 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('
            CREATE TABLE promo_code_limits (
                id SERIAL NOT NULL,
                promo_code_id INT NOT NULL,
                object_id INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                PRIMARY KEY(id)
            )');
        $this->sql('CREATE INDEX IDX_FDA2CB5B2FAE4625 ON promo_code_limits (promo_code_id)');
        $this->sql('CREATE UNIQUE INDEX promo_code_limit_unique ON promo_code_limits (promo_code_id, object_id, type)');
        $this->sql('
            ALTER TABLE
                promo_code_limits
            ADD
                CONSTRAINT FK_FDA2CB5B2FAE4625 FOREIGN KEY (promo_code_id) REFERENCES promo_codes (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
