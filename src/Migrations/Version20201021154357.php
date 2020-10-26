<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\FrameworkBundle\Migrations\MultidomainMigrationTrait;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20201021154357 extends AbstractMigration
{
    use MultidomainMigrationTrait;

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        foreach ($this->getAllDomainIds() as $domainId) {
            $aboutUsArticleId = $this->getSettingForNameAndDomainId('aboutUsArticleId', $domainId);
            $returnedGoodsArticleId = $this->getSettingForNameAndDomainId('returnedGoodsArticleId', $domainId);
            $changesInOrderArticleId = $this->getSettingForNameAndDomainId('changesInOrderArticleId', $domainId);
            $complaintGoodsArticleId = $this->getSettingForNameAndDomainId('complaintGoodsArticleId', $domainId);

            if ($aboutUsArticleId !== null)
            {
                $this->sql(
                    'INSERT INTO setting_values (name, domain_id, value, type) VALUES (\'aboutUsArticleId\', :domainId, :entityId, \'integer\')',
                    [
                        'domainId' => $domainId,
                        'entityId' => $aboutUsArticleId,
                    ]
                );
            }

            if ($returnedGoodsArticleId !== null)
            {
                $this->sql(
                    'INSERT INTO setting_values (name, domain_id, value, type) VALUES (\'returnedGoodsArticleId\', :domainId, :entityId, \'integer\')',
                    [
                        'domainId' => $domainId,
                        'entityId' => $returnedGoodsArticleId,
                    ]
                );
            }

            if ($changesInOrderArticleId !== null)
            {
                $this->sql(
                    'INSERT INTO setting_values (name, domain_id, value, type) VALUES (\'changesInOrderArticleId\', :domainId, :entityId, \'integer\')',
                    [
                        'domainId' => $domainId,
                        'entityId' => $changesInOrderArticleId,
                    ]
                );
            }

            if ($complaintGoodsArticleId !== null)
            {
                $this->sql(
                    'INSERT INTO setting_values (name, domain_id, value, type) VALUES (\'complaintGoodsArticleId\', :domainId, :entityId, \'integer\')',
                    [
                        'domainId' => $domainId,
                        'entityId' => $complaintGoodsArticleId,
                    ]
                );
            }
        }
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }

    /**
     * @param string $name
     * @param int $domainId
     * @return int|null
     */
    private function getSettingForNameAndDomainId(string $name, int $domainId): ?int
    {
        /*these article IDS exists on production*/
        $settingArray = [
            'aboutUsArticleId' => [1 => 5, 2 => 12],
            'returnedGoodsArticleId' => [1 => 7, 2 => 15],
            'changesInOrderArticleId' => [1 => 10, 2 => 18],
            'complaintGoodsArticleId' => [1 => 13, 2 => 19],
        ];

        if(isset($settingArray[$name][$domainId])) {
            return $settingArray[$name][$domainId];
        } else {
            return null;
        }
    }
}
