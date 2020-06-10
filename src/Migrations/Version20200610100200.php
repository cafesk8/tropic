<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\FrameworkBundle\Migrations\MultidomainMigrationTrait;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200610100200 extends AbstractMigration
{
    use MultidomainMigrationTrait;

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        foreach ($this->getAllDomainIds() as $domainId) {
            $this->sql('INSERT INTO mail_templates(name, domain_id, subject, body, send_mail) VALUES (:name, :domainId, :subject, :body, TRUE)', [
                'name' => 'watch_dog',
                'domainId' => $domainId,
                'subject' => t('Hlídač ceny a dostupnosti', [], 'dataFixtures'),
                'body' => t('Dobrý den,<br /><br />produkt <a href="{product_url}">{product_name}</a>, který jste se rozhodli sledovat, je nyní k dispozici za vámi požadovanou cenu.<br /><br />S pozdravem<br />tým Tropic Fishing', [], 'dataFixtures'),
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
