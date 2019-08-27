<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\FrameworkBundle\Migrations\MultidomainMigrationTrait;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190827100743 extends AbstractMigration
{
    use MultidomainMigrationTrait;

    private const ARTICLE_ID_SEQUENCE_NAME = 'articles_id_seq';
    private const PRODUCT_SIZE_ARTICLE_ID = 'productSizeArticleId';

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        foreach ($this->getAllDomainIds() as $domainId) {
            $productSizeArticleId = $this->createProductSizeArticle($domainId);

            $this->createProductSizeArticleUrl($productSizeArticleId, $domainId);
            $this->createSettings($productSizeArticleId, $domainId);
        }
    }

    /**
     * @param int $domainId
     * @return int
     */
    private function createProductSizeArticle(int $domainId): int
    {
        $position = $this->sql(
            'SELECT MAX(position) FROM articles WHERE placement = \'none\' AND domain_id = :domainId;',
            ['domainId' => $domainId]
        )->fetchColumn();

        $this->sql('INSERT INTO articles (domain_id, position, name, text, placement, hidden, created_at) VALUES (:domainId, :position, :name, :text, \'none\', false, \'NOW()\');', [
            'domainId' => $domainId,
            'position' => ++$position,
            'name' => 'Tabulka velikostí pro produkty',
            'text' => '',
        ]);

        return (int)$this->connection->lastInsertId(self::ARTICLE_ID_SEQUENCE_NAME);
    }

    /**
     * @param int $articleId
     * @param int $domainId
     */
    private function createProductSizeArticleUrl(int $articleId, int $domainId): void
    {
        $this->sql('INSERT INTO friendly_urls (domain_id, slug, route_name, entity_id, main) VALUES (:domainId, :slug, \'front_article_detail\', :entityId, true);', [
            'domainId' => $domainId,
            'slug' => 'tabulka-velikosti-produktu/',
            'entityId' => $articleId,
        ]);
    }

    /**
     * @param int $articleId
     * @param int $domainId
     */
    private function createSettings(int $articleId, int $domainId): void
    {
        $this->sql('INSERT INTO setting_values (name, domain_id, value, type) VALUES (:name, :domainId, :id, \'integer\');', [
            'name' => self::PRODUCT_SIZE_ARTICLE_ID,
            'domainId' => $domainId,
            'id' => $articleId,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function down(Schema $schema): void
    {
    }
}
