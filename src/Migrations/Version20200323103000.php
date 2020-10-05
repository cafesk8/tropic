<?php

declare(strict_types=1);

namespace App\Migrations;

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
                'name' => OrderGiftCertificateMail::MAIL_TEMPLATE_DEFAULT_NAME,
                'domainId' => $domainId,
                'subject' => t('Dárkový poukaz', [], 'dataFixtures'),
                'body' => t('Dobrý den,<br /><br />zakoupil/a jste dárkový poukaz s kódem <b>{gift_certificate_code}</b> v hodnotě <b>{gift_certificate_value} {gift_certificate_currency}</b>. Tento poukaz však bude aktivován až po obdržení platby za objednávku {order_number}, o čemž vás budeme informovat dalším emailem.', [], 'dataFixtures'),
            ]);
            $this->sql('INSERT INTO mail_templates(name, domain_id, subject, body, send_mail) VALUES (:name, :domainId, :subject, :body, TRUE)', [
                'name' => OrderGiftCertificateMail::MAIL_TEMPLATE_ACTIVATED_NAME,
                'domainId' => $domainId,
                'subject' => t('Dárkový poukaz - aktivován', [], 'dataFixtures'),
                'body' => t('Dobrý den,<br /><br />váš dárkový poukaz s kódem <b>{gift_certificate_code}</b> v hodnotě <b>{gift_certificate_value} {gift_certificate_currency}</b> byl právě aktivován. Certifikát je platný do {gift_certificate_valid_until}. Děkujeme za váš nákup.', [], 'dataFixtures'),
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
