<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use App\Model\Mail\AllMailTemplatesData;
use App\Model\Order\GiftCertificate\Mail\OrderGiftCertificateMail;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Mail\MailTemplate;
use Shopsys\FrameworkBundle\Model\Mail\MailTemplateData;
use Shopsys\FrameworkBundle\Model\Mail\MailTemplateDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Mail\MailTemplateFactoryInterface;

class MailTemplateDataFixture extends AbstractReferenceFixture
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Mail\MailTemplateFactoryInterface
     */
    protected $mailTemplateFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Mail\MailTemplateDataFactoryInterface
     */
    protected $mailTemplateDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    protected $domain;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Mail\MailTemplateFactoryInterface $mailTemplateFactory
     * @param \Shopsys\FrameworkBundle\Model\Mail\MailTemplateDataFactoryInterface $mailTemplateDataFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        MailTemplateFactoryInterface $mailTemplateFactory,
        MailTemplateDataFactoryInterface $mailTemplateDataFactory,
        Domain $domain
    ) {
        $this->mailTemplateFactory = $mailTemplateFactory;
        $this->mailTemplateDataFactory = $mailTemplateDataFactory;
        $this->domain = $domain;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $mailTemplateData = $this->mailTemplateDataFactory->create();
        $mailTemplateData->sendMail = true;

        foreach ($this->domain->getAll() as $domainConfig) {
            $domainId = $domainConfig->getId();
            $locale = $domainConfig->getLocale();
            $mailTemplateData->subject = t('Děkujeme za objednávku č. {number} ze dne {date}', [], 'dataFixtures', $locale);
            $mailTemplateData->body = t('Dobrý den,<br /><br />'
                . 'Vaše objednávka byla úspěšně vytvořena.<br /><br />'
                . 'O dalších stavech objednávky Vás budeme informovat.<br />'
                . 'Čislo objednávky: {number} <br />'
                . 'Datum a čas vytvoření: {date} <br />'
                . 'URL adresa eshopu: {url} <br />'
                . 'URL adresa na detail objednávky: {order_detail_url} <br />'
                . 'Doprava: {transport} <br />'
                . 'Číslo pro sledování zásilky: {tracking_number} <br />'
                . 'URL pro sledování zásilky: {tracking_url} <br />'
                . 'Platba: {payment} <br />'
                . 'Celková cena s DPH: {total_price} <br />'
                . 'Fakturační adresa:<br /> {billing_address} <br />'
                . 'Doručovací adresa: {delivery_address} <br />'
                . 'Poznámka: {note} <br />'
                . 'Produkty: {products} <br />'
                . '{transport_instructions} <br />'
                . '{payment_instructions}', [], 'dataFixtures', $locale);

            $this->createMailTemplate($manager, 'order_status_1', $mailTemplateData, $domainId);

            $mailTemplateData->sendMail = false;
            $mailTemplateData->subject = t('Stav objednávky se změnil', [], 'dataFixtures', $locale);
            $mailTemplateData->body = t('Vážený zákazníku, <br /><br />'
                . 'Vaše objednávka se zpracovává.', [], 'dataFixtures', $locale);

            $this->createMailTemplate($manager, 'order_status_2', $mailTemplateData, $domainId);

            $mailTemplateData->subject = t('Stav objednávky se změnil', [], 'dataFixtures', $locale);
            $mailTemplateData->body = t('Vážený zákazníku, <br /><br />'
                . 'zpracování objednávky bylo dokončeno.', [], 'dataFixtures', $locale);

            $this->createMailTemplate($manager, 'order_status_3', $mailTemplateData, $domainId);

            $mailTemplateData->subject = t('Stav objednávky se změnil', [], 'dataFixtures', $locale);
            $mailTemplateData->body = t('Vážený zákazníku, <br /><br />'
                . 'Vaše objednávka byla zrušena.', [], 'dataFixtures', $locale);

            $this->createMailTemplate($manager, 'order_status_4', $mailTemplateData, $domainId);

            $mailTemplateData->sendMail = true;
            $mailTemplateData->subject = t('Žádost o heslo', [], 'dataFixtures', $locale);
            $mailTemplateData->body = t('Vážený zákazníku,<br /><br />'
                . 'na tomto odkazu můžete nastavit nové heslo: <a href="{new_password_url}">{new_password_url}</a>', [], 'dataFixtures', $locale);

            $this->createMailTemplate($manager, MailTemplate::RESET_PASSWORD_NAME, $mailTemplateData, $domainId);

            $mailTemplateData->subject = t('Registrace byla dokončena', [], 'dataFixtures', $locale);
            $mailTemplateData->body = t('Vážený zákazníku, <br /><br />'
                . 'Vaše registrace je dokončena. <br />'
                . 'Jméno: {first_name} {last_name}<br />'
                . 'E-mail: {email}<br />'
                . 'Adresa e-shopu: {url}<br />'
                . 'Přihlašovací stránka: {login_page}', [], 'dataFixtures', $locale);

            $this->createMailTemplate($manager, MailTemplate::REGISTRATION_CONFIRM_NAME, $mailTemplateData, $domainId);

            $mailTemplateData->subject = t('Přehled osobních údajů - {domain}', [], 'dataFixtures', $locale);
            $mailTemplateData->body = t('Vážený zákazníku,<br /><br />
            na základě vašeho zadaného emailu {e-mail}, Vám zasíláme odkaz na zobrazení osobních údajů. Klikem na odkaz níže se dostanete na stránku s <br/>  
            přehledem všech osobních údajů, které k Vašemu e-mailu evidujeme na našem e-shopu {domain}.<br/><br/>
            Pro zobrazení osobních údajů klikněte zde - {url}<br/>
            Odkaz je platný 24 hodin.<br/><br/>
            S pozdravem<br/>
            tým {domain}', [], 'dataFixtures', $locale);

            $this->createMailTemplate($manager, MailTemplate::PERSONAL_DATA_ACCESS_NAME, $mailTemplateData, $domainId);

            $mailTemplateData->subject = t('Export osobních údajů - {domain}', [], 'dataFixtures', $locale);
            $mailTemplateData->body = t('Vážený zákazníku,<br/><br/>
            na základě vašeho zadaného emailu {e-mail}, Vám zasíláme odkaz ke stažení Vašich<br/>
            údajů evidovaných na našem internetovém obchodě ve strojově čitelném formátu.<br/>
            Klikem na odkaz se dostanete na stránku s s možností stažení těchto informací, které k<br/>
            Vašemu e-mailu evidujeme na našem internetovém obchodu {domain}.<br/><br/>
            Pro přechod na stažení údajů, prosím, klikněte zde - {url}<br/>
            Odkaz je platný 24 hodin.<br/><br/>
            S pozdravem<br/>
            tým {domain}', [], 'dataFixtures', $locale);

            $this->createMailTemplate($manager, MailTemplate::PERSONAL_DATA_EXPORT_NAME, $mailTemplateData, $domainId);

            $mailTemplateData->subject = t('Dárkový poukaz', [], 'dataFixtures', $locale);
            $mailTemplateData->body = t('Dobrý den,<br /><br />
            zakoupil/a jste dárkový poukaz s kódem 
            <b>' . OrderGiftCertificateMail::VARIABLE_GIFT_CERTIFICATE_CODE . '</b> v hodnotě 
            <b>' . OrderGiftCertificateMail::VARIABLE_GIFT_CERTIFICATE_VALUE . ' ' . OrderGiftCertificateMail::VARIABLE_GIFT_CERTIFICATE_CURRENCY . '</b>. 
            Tento poukaz však bude aktivován až po obdržení platby za objednávku ' . OrderGiftCertificateMail::VARIABLE_ORDER_NUMBER . ', 
            o čemž vás budeme informovat dalším emailem.', [], 'dataFixtures', $locale);

            $this->createMailTemplate($manager, AllMailTemplatesData::GIFT_CERTIFICATE, $mailTemplateData, $domainId);

            $mailTemplateData->subject = t('Dárkový poukaz - aktivován', [], 'dataFixtures', $locale);
            $mailTemplateData->body = t('Dobrý den,<br /><br />
            váš dárkový poukaz s kódem 
            <b>' . OrderGiftCertificateMail::VARIABLE_GIFT_CERTIFICATE_CODE . '</b> v hodnotě 
            <b>' . OrderGiftCertificateMail::VARIABLE_GIFT_CERTIFICATE_VALUE . ' ' . OrderGiftCertificateMail::VARIABLE_GIFT_CERTIFICATE_CURRENCY . '</b> byl právě aktivován. 
            Certifikát je platný do ' . OrderGiftCertificateMail::VARIABLE_GIFT_CERTIFICATE_VALID_UNTIL . '. 
            Děkujeme za váš nákup.', [], 'dataFixtures', $locale);

            $this->createMailTemplate($manager, AllMailTemplatesData::GIFT_CERTIFICATE_ACTIVATED, $mailTemplateData, $domainId);
        }
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $manager
     * @param string $name
     * @param \Shopsys\FrameworkBundle\Model\Mail\MailTemplateData $mailTemplateData
     * @param int $domainId
     */
    protected function createMailTemplate(
        ObjectManager $manager,
        string $name,
        MailTemplateData $mailTemplateData,
        int $domainId
    ) {
        $repository = $manager->getRepository(MailTemplate::class);

        $mailTemplate = $repository->findOneBy([
            'name' => $name,
            'domainId' => $domainId,
        ]);

        if ($mailTemplate === null) {
            $mailTemplate = $this->mailTemplateFactory->create($name, $domainId, $mailTemplateData);
        } else {
            $mailTemplate->edit($mailTemplateData);
        }

        $manager->persist($mailTemplate);
        $manager->flush($mailTemplate);
    }
}
