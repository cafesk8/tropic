<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Schema\Schema;
use Shopsys\FrameworkBundle\Migrations\MultidomainMigrationTrait;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190823130557 extends AbstractMigration
{
    use MultidomainMigrationTrait;

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        foreach ($this->getAllDomainIds() as $domainId) {
            $this->sql(
                'INSERT INTO articles (position, domain_id, placement, name, hidden, created_at) VALUES (0, :domain_id, \'none\', :name, FALSE, :createdAt)',
                [
                    'domain_id' => $domainId,
                    'name' => 'Věrnostní program',
                    'createdAt' => (new DateTimeImmutable())->format(DateTimeInterface::ISO8601),
                ]
            );

            $entityId = $this->connection->lastInsertId('articles_id_seq');
            $this->sql(
                'INSERT INTO friendly_urls (domain_id, slug, route_name, entity_id, main) VALUES (:domainId, :slug, \'front_article_detail\', :entityId, TRUE)',
                [
                    'domainId' => $domainId,
                    'slug' => 'bushmanclub/',
                    'entityId' => $entityId,
                ]
            );

            $this->sql(
                'INSERT INTO setting_values (name, domain_id, value, type) VALUES (\'bushmanClubArticleId\', :domainId, :entityId, \'integer\')',
                [
                    'domainId' => $domainId,
                    'entityId' => $entityId,
                ]
            );
        }
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
