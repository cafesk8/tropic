<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200317162807 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('
            CREATE TABLE order_gift_certificates (
                id SERIAL NOT NULL,
                order_id INT NOT NULL,
                gift_certificate_id INT NOT NULL,
                PRIMARY KEY(id)
            )');
        $this->sql('CREATE INDEX IDX_21CF61C18D9F6D38 ON order_gift_certificates (order_id)');
        $this->sql('CREATE INDEX IDX_21CF61C1A97C76A8 ON order_gift_certificates (gift_certificate_id)');
        $this->sql('
            ALTER TABLE
                order_gift_certificates
            ADD
                CONSTRAINT FK_21CF61C18D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->sql('
            ALTER TABLE
                order_gift_certificates
            ADD
                CONSTRAINT FK_21CF61C1A97C76A8 FOREIGN KEY (gift_certificate_id) REFERENCES promo_codes (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
