<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200129161821 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('DROP TABLE card_eans');
        $this->sql('ALTER TABLE orders DROP COLUMN customer_ean');
        $this->sql('ALTER TABLE user_transfer_ids_and_eans DROP COLUMN ean');
        $this->sql('ALTER TABLE user_transfer_ids_and_eans RENAME TO user_transfer_ids');
        $this->sql('ALTER TABLE users DROP COLUMN ean');
        $this->sql('CREATE UNIQUE INDEX email_domain ON users (email, domain_id)');
        $this->sql('ALTER TABLE user_transfer_ids ADD PRIMARY KEY (customer_id, transfer_id)');
        $this->sql('ALTER INDEX idx_891e458f9395c3f3 RENAME TO IDX_DDF87D389395C3F3');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
