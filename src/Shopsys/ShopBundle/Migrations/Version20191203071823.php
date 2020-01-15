<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\FrameworkBundle\Migrations\MultidomainMigrationTrait;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20191203071823 extends AbstractMigration
{
    use MultidomainMigrationTrait;

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('INSERT INTO order_statuses (type, check_order_ready_status) VALUES (:type, false);', ['type' => 9]);
        $orderStatusId = (int)$this->connection->lastInsertId('order_statuses_id_seq');

        $this->createOrderStatusTranslationAndMailTemplate($orderStatusId, 'Vrácené zboží');
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
