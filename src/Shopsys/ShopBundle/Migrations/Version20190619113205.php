<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190619113205 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->sql('
            CREATE TABLE blog_article_products (
                blog_article_id INT NOT NULL,
                product_id INT NOT NULL,
                PRIMARY KEY(blog_article_id, product_id)
            )');
        $this->sql('CREATE INDEX IDX_925185D79452A475 ON blog_article_products (blog_article_id)');
        $this->sql('CREATE INDEX IDX_925185D74584665A ON blog_article_products (product_id)');
        $this->sql('
            ALTER TABLE
                blog_article_products
            ADD
                CONSTRAINT FK_925185D79452A475 FOREIGN KEY (blog_article_id) REFERENCES blog_articles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->sql('
            ALTER TABLE
                blog_article_products
            ADD
                CONSTRAINT FK_925185D74584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
