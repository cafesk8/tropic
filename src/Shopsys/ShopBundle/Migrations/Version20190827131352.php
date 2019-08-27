<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190827131352 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE users ALTER first_name TYPE VARCHAR(60)');
        $this->sql('ALTER TABLE users ALTER last_name TYPE VARCHAR(30)');
        $this->sql('ALTER TABLE users ALTER email TYPE VARCHAR(50)');
        $this->sql('ALTER TABLE orders ALTER first_name TYPE VARCHAR(60)');
        $this->sql('ALTER TABLE orders ALTER last_name TYPE VARCHAR(30)');
        $this->sql('ALTER TABLE orders ALTER email TYPE VARCHAR(50)');
        $this->sql('ALTER TABLE orders ALTER telephone TYPE VARCHAR(20)');
        $this->sql('ALTER TABLE orders ALTER company_number TYPE VARCHAR(20)');
        $this->sql('ALTER TABLE orders ALTER company_tax_number TYPE VARCHAR(30)');
        $this->sql('ALTER TABLE orders ALTER postcode TYPE VARCHAR(6)');
        $this->sql('ALTER TABLE orders ALTER delivery_postcode TYPE VARCHAR(6)');
        $this->sql('ALTER TABLE orders ALTER delivery_first_name TYPE VARCHAR(60)');
        $this->sql('ALTER TABLE orders ALTER delivery_last_name TYPE VARCHAR(30)');
        $this->sql('ALTER TABLE billing_addresses ALTER company_number TYPE VARCHAR(20)');
        $this->sql('ALTER TABLE billing_addresses ALTER company_tax_number TYPE VARCHAR(30)');
        $this->sql('ALTER TABLE billing_addresses ALTER postcode TYPE VARCHAR(6)');
        $this->sql('ALTER TABLE delivery_addresses ALTER postcode TYPE VARCHAR(6)');
        $this->sql('ALTER TABLE delivery_addresses ALTER telephone TYPE VARCHAR(20)');
        $this->sql('ALTER TABLE delivery_addresses ALTER first_name TYPE VARCHAR(60)');
        $this->sql('ALTER TABLE delivery_addresses ALTER last_name TYPE VARCHAR(30)');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
