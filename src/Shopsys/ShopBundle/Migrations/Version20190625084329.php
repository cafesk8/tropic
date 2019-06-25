<?php

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190625084329 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->sql('
            CREATE TABLE category_blog_articles (
                category_id INT NOT NULL,
                blog_article_id INT NOT NULL,
                position INT NOT NULL,
                PRIMARY KEY(category_id, blog_article_id)
            )');
        $this->sql('CREATE INDEX IDX_D55B300412469DE2 ON category_blog_articles (category_id)');
        $this->sql('CREATE INDEX IDX_D55B30049452A475 ON category_blog_articles (blog_article_id)');
        $this->sql('
            ALTER TABLE
                category_blog_articles
            ADD
                CONSTRAINT FK_3FEAA57412469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->sql('
            ALTER TABLE
                category_blog_articles
            ADD
                CONSTRAINT FK_3FEAA5749452A475 FOREIGN KEY (blog_article_id) REFERENCES blog_articles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
