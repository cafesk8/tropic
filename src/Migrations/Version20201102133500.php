<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use PDO;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20201102133500 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $results = $this->sql('SELECT DA.customer_id, BA.street, BA.city, BA.postcode FROM delivery_addresses DA JOIN billing_addresses BA ON BA.customer_id = DA.customer_id WHERE DA.street = \'\' AND BA.street != \'\'')->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $result) {
            $this->sql('UPDATE delivery_addresses SET street = :street, city = :city, postcode = :postcode WHERE customer_id = :customerId', [
                'street' => $result['street'],
                'city' => $result['city'],
                'postcode' => $result['postcode'],
                'customerId' => $result['customer_id'],
            ]);
        }
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
