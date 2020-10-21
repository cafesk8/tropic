<?php

declare(strict_types=1);

namespace App\Migrations;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20201018202516 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $dateTime = (new DateTimeImmutable('1970-01-01'))->format(DateTimeInterface::ISO8601);
        $this->sql('INSERT INTO setting_values (name, domain_id, value, type) VALUES (\'lastSentMServerError500Info\', :domainId, :dateTime, \'datetime\')', [
            'domainId' => 0,
            'dateTime' => $dateTime,
        ]);
        $this->sql('INSERT INTO setting_values (name, domain_id, value, type) VALUES (\'lastSentMServerTimeoutInfo\', :domainId, :dateTime, \'datetime\')', [
            'domainId' => 0,
            'dateTime' => $dateTime,
        ]);
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
