<?php

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190618121659 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->sql('ALTER TABLE blog_articles ADD visible_on_homepage BOOLEAN NOT NULL DEFAULT TRUE');
        $this->sql('ALTER TABLE blog_articles ALTER visible_on_homepage DROP DEFAULT');
        $this->sql('ALTER TABLE blog_articles ADD publish_date DATE NOT NULL DEFAULT \'' . date('Y-m-d') . '\'');
        $this->sql('ALTER TABLE blog_articles ALTER publish_date DROP DEFAULT');
        $this->sql('ALTER TABLE blog_article_translations ADD perex TEXT DEFAULT NULL');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
