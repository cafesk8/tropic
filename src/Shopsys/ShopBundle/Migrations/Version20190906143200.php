<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\FrameworkBundle\Migrations\MultidomainMigrationTrait;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190906143200 extends AbstractMigration
{
    use MultidomainMigrationTrait;

    private const NEW_ORDER_STATUES_BY_TYPE = [
        '5' => 'Částečné vykrytí',
        '6' => 'Částečné vykrytí - Odběrné místo',
        '7' => 'Objednávka vykrytá',
        '8' => 'Objednávka vykrytá - Odběrné místo',
    ];

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        foreach (self::NEW_ORDER_STATUES_BY_TYPE as $type => $name) {
            $this->sql('INSERT INTO order_statuses (type) VALUES (:type);', ['type' => $type]);
            $orderStatusId = (int)$this->connection->lastInsertId('order_statuses_id_seq');

            $this->createOrderStatusTranslationAndMailTemplate($orderStatusId, $name);
        }
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }

    /**
     * @param int $orderStatusId
     * @param string $name
     */
    private function createOrderStatusTranslationAndMailTemplate(int $orderStatusId, string $name): void
    {
        foreach ($this->getAllDomainIds() as $domainId) {
            $this->sql('INSERT INTO order_status_translations (translatable_id, name, locale) 
                VALUES (:translatable_id, :name, :locale)', [
                    'translatable_id' => $orderStatusId,
                    'name' => $name,
                    'locale' => $this->getDomainLocale($domainId),
            ]);

            $this->sql('INSERT INTO mail_templates (name, domain_id, subject, body, send_mail) 
                VALUES (:name, :domainId, :subject, :body, :sendMail)', [
                    'name' => 'order_status_' . $orderStatusId,
                    'domainId' => $domainId,
                    'subject' => 'Stav objednávky se změnil',
                    'body' => 'Stav objednávky se změnil na ' . $name,
                    'sendMail' => 0,
            ]);
        }
    }
}
