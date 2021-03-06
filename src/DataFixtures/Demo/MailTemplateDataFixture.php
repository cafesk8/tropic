<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use App\Component\WatchDog\WatchDogMail;
use App\Model\Order\GiftCertificate\Mail\OrderGiftCertificateMail;
use Doctrine\Persistence\ObjectManager;
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
     * @param \Doctrine\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $mailTemplateData = $this->mailTemplateDataFactory->create();
        $mailTemplateData->sendMail = true;

        foreach ($this->domain->getAll() as $domainConfig) {
            $domainId = $domainConfig->getId();
            $locale = $domainConfig->getLocale();
            $mailTemplateData->subject = t('D??kujeme za objedn??vku ??. {number} ze dne {date}', [], 'dataFixtures', $locale);
            $mailTemplateData->body = t('Dobr?? den,<br /><br />'
                . 'Va??e objedn??vka byla ??sp????n?? vytvo??ena.<br /><br />'
                . 'O dal????ch stavech objedn??vky V??s budeme informovat.<br />'
                . '??islo objedn??vky: {number} <br />'
                . 'Datum a ??as vytvo??en??: {date} <br />'
                . 'URL adresa eshopu: {url} <br />'
                . 'URL adresa na detail objedn??vky: {order_detail_url} <br />'
                . 'Doprava: {transport} <br />'
                . '????slo pro sledov??n?? z??silky: {tracking_number} <br />'
                . 'URL pro sledov??n?? z??silky: {tracking_url} <br />'
                . 'Platba: {payment} <br />'
                . 'Celkov?? cena s DPH: {total_price} <br />'
                . 'Faktura??n?? adresa:<br /> {billing_address} <br />'
                . 'Doru??ovac?? adresa: {delivery_address} <br />'
                . 'Pozn??mka: {note} <br />'
                . 'Produkty: {products} <br />'
                . '{transport_instructions} <br />'
                . '{payment_instructions}', [], 'dataFixtures', $locale);

            $this->createMailTemplate($manager, 'order_status_1', $mailTemplateData, $domainId);

            $mailTemplateData->sendMail = false;
            $mailTemplateData->subject = t('Stav objedn??vky se zm??nil', [], 'dataFixtures', $locale);
            $mailTemplateData->body = t('V????en?? z??kazn??ku, <br /><br />'
                . 'Va??e objedn??vka se zpracov??v??.', [], 'dataFixtures', $locale);

            $this->createMailTemplate($manager, 'order_status_2', $mailTemplateData, $domainId);

            $mailTemplateData->subject = t('Stav objedn??vky se zm??nil', [], 'dataFixtures', $locale);
            $mailTemplateData->body = t('V????en?? z??kazn??ku, <br /><br />'
                . 'zpracov??n?? objedn??vky bylo dokon??eno.', [], 'dataFixtures', $locale);

            $this->createMailTemplate($manager, 'order_status_3', $mailTemplateData, $domainId);

            $mailTemplateData->subject = t('Stav objedn??vky se zm??nil', [], 'dataFixtures', $locale);
            $mailTemplateData->body = t('V????en?? z??kazn??ku, <br /><br />'
                . 'Va??e objedn??vka byla zru??ena.', [], 'dataFixtures', $locale);

            $this->createMailTemplate($manager, 'order_status_4', $mailTemplateData, $domainId);

            $mailTemplateData->sendMail = true;
            $mailTemplateData->subject = t('????dost o heslo', [], 'dataFixtures', $locale);
            $mailTemplateData->body = t('V????en?? z??kazn??ku,<br /><br />'
                . 'na tomto odkazu m????ete nastavit nov?? heslo: <a href="{new_password_url}">{new_password_url}</a>', [], 'dataFixtures', $locale);

            $this->createMailTemplate($manager, MailTemplate::RESET_PASSWORD_NAME, $mailTemplateData, $domainId);

            $mailTemplateData->subject = t('Registrace byla dokon??ena', [], 'dataFixtures', $locale);
            $mailTemplateData->body = t('V????en?? z??kazn??ku, <br /><br />'
                . 'Va??e registrace je dokon??ena. <br />'
                . 'Jm??no: {first_name} {last_name}<br />'
                . 'E-mail: {email}<br />'
                . 'Adresa e-shopu: {url}<br />'
                . 'P??ihla??ovac?? str??nka: {login_page}', [], 'dataFixtures', $locale);

            $this->createMailTemplate($manager, MailTemplate::REGISTRATION_CONFIRM_NAME, $mailTemplateData, $domainId);

            $mailTemplateData->subject = t('P??ehled osobn??ch ??daj?? - {domain}', [], 'dataFixtures', $locale);
            $mailTemplateData->body = t('V????en?? z??kazn??ku,<br /><br />
            na z??klad?? va??eho zadan??ho emailu {email}, V??m zas??l??me odkaz na zobrazen?? osobn??ch ??daj??. Klikem na odkaz n????e se dostanete na str??nku s <br/>  
            p??ehledem v??ech osobn??ch ??daj??, kter?? k Va??emu e-mailu evidujeme na na??em e-shopu {domain}.<br/><br/>
            Pro zobrazen?? osobn??ch ??daj?? klikn??te zde - {url}<br/>
            Odkaz je platn?? 24 hodin.<br/><br/>
            S pozdravem<br/>
            t??m {domain}', [], 'dataFixtures', $locale);

            $this->createMailTemplate($manager, MailTemplate::PERSONAL_DATA_ACCESS_NAME, $mailTemplateData, $domainId);

            $mailTemplateData->subject = t('Export osobn??ch ??daj?? - {domain}', [], 'dataFixtures', $locale);
            $mailTemplateData->body = t('V????en?? z??kazn??ku,<br/><br/>
            na z??klad?? va??eho zadan??ho emailu {email}, V??m zas??l??me odkaz ke sta??en?? Va??ich<br/>
            ??daj?? evidovan??ch na na??em internetov??m obchod?? ve strojov?? ??iteln??m form??tu.<br/>
            Klikem na odkaz se dostanete na str??nku s s mo??nost?? sta??en?? t??chto informac??, kter?? k<br/>
            Va??emu e-mailu evidujeme na na??em internetov??m obchodu {domain}.<br/><br/>
            Pro p??echod na sta??en?? ??daj??, pros??m, klikn??te zde - {url}<br/>
            Odkaz je platn?? 24 hodin.<br/><br/>
            S pozdravem<br/>
            t??m {domain}', [], 'dataFixtures', $locale);

            $this->createMailTemplate($manager, MailTemplate::PERSONAL_DATA_EXPORT_NAME, $mailTemplateData, $domainId);

            $mailTemplateData->subject = t('D??rkov?? poukaz', [], 'dataFixtures', $locale);
            $mailTemplateData->body = t('Dobr?? den,<br /><br />
            zakoupil/a jste d??rkov?? poukaz s k??dem 
            <b>{gift_certificate_code}</b> v hodnot?? 
            <b>{gift_certificate_value} {gift_certificate_currency}</b>. 
            Tento poukaz v??ak bude aktivov??n a?? po obdr??en?? platby za objedn??vku {order_number}, 
            o ??em?? v??s budeme informovat dal????m emailem.', [], 'dataFixtures', $locale);

            $this->createMailTemplate($manager, OrderGiftCertificateMail::MAIL_TEMPLATE_DEFAULT_NAME, $mailTemplateData, $domainId);

            $mailTemplateData->subject = t('D??rkov?? poukaz - aktivov??n', [], 'dataFixtures', $locale);
            $mailTemplateData->body = t('Dobr?? den,<br /><br />
            v???? d??rkov?? poukaz s k??dem 
            <b>{gift_certificate_code}</b> v hodnot?? 
            <b>{gift_certificate_value} {gift_certificate_currency}</b> byl pr??v?? aktivov??n. 
            Certifik??t je platn?? do {gift_certificate_valid_until}. 
            D??kujeme za v???? n??kup.', [], 'dataFixtures', $locale);

            $this->createMailTemplate($manager, OrderGiftCertificateMail::MAIL_TEMPLATE_ACTIVATED_NAME, $mailTemplateData, $domainId);

            $mailTemplateData->subject = t('Hl??da?? ceny a dostupnosti', [], 'dataFixtures', $locale);
            $mailTemplateData->body = t('Dobr?? den,<br /><br />
            produkt <a href="{product_url}">{product_name}</a>, 
            kter?? jste se rozhodli sledovat, je nyn?? k dispozici za v??mi po??adovanou cenu.<br /><br />
            S pozdravem<br />
            t??m Tropic Fishing', [], 'dataFixtures', $locale);

            $this->createMailTemplate($manager, WatchDogMail::MAIL_TEMPLATE_WATCH_DOG, $mailTemplateData, $domainId);
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
