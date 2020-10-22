<?php

declare(strict_types=1);

namespace App\Migrations;

use App\Component\MergadoTransportType\MergadoTransportTypeFacade;
use App\Model\Transport\Transport;
use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20201022142033 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $zasilkovnaTransportTypes = [Transport::TYPE_ZASILKOVNA_CZ, Transport::TYPE_ZASILKOVNA_SK];
        $this->sql(
            'UPDATE transports
            SET mergado_transport_type = :zasilkovnaMergadoTransportType
            WHERE transport_type IN (:zasilkovnaTransportTypes)', [
                'zasilkovnaMergadoTransportType' => MergadoTransportTypeFacade::ZASILKOVNA,
                'zasilkovnaTransportTypes' => implode(',', $zasilkovnaTransportTypes),
            ]
        );
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
