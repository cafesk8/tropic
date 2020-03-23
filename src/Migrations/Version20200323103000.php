<?php

declare(strict_types=1);

namespace App\Migrations;

use App\Model\Mail\AllMailTemplatesData;
use App\Model\Order\GiftCertificate\Mail\OrderGiftCertificateMail;
use Doctrine\DBAL\Schema\Schema;
use Shopsys\FrameworkBundle\Migrations\MultidomainMigrationTrait;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200323103000 extends AbstractMigration
{
    use MultidomainMigrationTrait;

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        foreach ($this->getAllDomainIds() as $domainId) {
            $this->sql('INSERT INTO mail_templates(name, domain_id, subject, body, send_mail) VALUES (:name, :domainId, :subject, :body, TRUE)', [
                'name' => AllMailTemplatesData::GIFT_CERTIFICATE,
                'domainId' => $domainId,
                'subject' => 'Dárkový poukaz',
                'body' => 'Dobrý den,<br /><br />zakoupil/a jste dárkový poukaz s kódem <b>' . OrderGiftCertificateMail::GIFT_CERTIFICATE_CODE . '</b> v hodnotě <b>' . OrderGiftCertificateMail::GIFT_CERTIFICATE_VALUE . ' ' . OrderGiftCertificateMail::GIFT_CERTIFICATE_CURRENCY . '</b>. Tento poukaz však bude aktivován až po obdržení platby za objednávku ' . OrderGiftCertificateMail::ORDER_NUMBER . ', o čemž vás budeme informovat dalším emailem.',
            ]);
            $this->sql('INSERT INTO mail_templates(name, domain_id, subject, body, send_mail) VALUES (:name, :domainId, :subject, :body, TRUE)', [
                'name' => AllMailTemplatesData::GIFT_CERTIFICATE_ACTIVATED,
                'domainId' => $domainId,
                'subject' => 'Dárkový poukaz - aktivován',
                'body' => 'Dobrý den,<br /><br />váš dárkový poukaz s kódem <b>' . OrderGiftCertificateMail::GIFT_CERTIFICATE_CODE . '</b> v hodnotě <b>' . OrderGiftCertificateMail::GIFT_CERTIFICATE_VALUE . ' ' . OrderGiftCertificateMail::GIFT_CERTIFICATE_CURRENCY . '</b> byl právě aktivován. Děkujeme za váš nákup.',
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
