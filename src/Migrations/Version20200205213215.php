<?php

declare(strict_types=1);

namespace App\Migrations;

use App\Component\Domain\DomainHelper;
use Doctrine\DBAL\Schema\Schema;
use Shopsys\FrameworkBundle\Migrations\MultidomainMigrationTrait;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200205213215 extends AbstractMigration
{
    use MultidomainMigrationTrait;

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        foreach ($this->getAllDomainIds() as $domainId) {
            $contactFormMainTextCount = $this->sql(
                'SELECT COUNT(*) FROM setting_values WHERE name = \'contactFormMainText\' AND domain_id = :domainId;',
                [
                    'domainId' => $domainId,
                ]
            )->fetchColumn(0);
            if ($contactFormMainTextCount <= 0) {
                $this->sql('INSERT INTO setting_values (name, domain_id, value, type) VALUES (\'contactFormMainText\', :domainId, :text, \'string\')', [
                    'domainId' => $domainId,
                    'text' => $this->getContactFormTextByLocale($this->getDomainLocale($domainId)),
                ]);
            }
        }
    }

    /**
     * @param string $locale
     * @return string
     */
    private function getContactFormTextByLocale(string $locale): string
    {
        switch ($locale) {
            case DomainHelper::CZECH_LOCALE:
                return 'Máš dotaz?';
            case DomainHelper::SLOVAK_LOCALE:
                return 'Máš otázku?';
            case DomainHelper::GERMAN_LOCALE:
                return 'Hast du eine Frage?';
            default:
                return 'Do you have a question?';
        }
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
