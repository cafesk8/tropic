<?php

declare(strict_types=1);

namespace App\Model\Order\GiftCertificate\Mail;

use App\Component\Setting\Setting;
use App\Model\Order\GiftCertificate\OrderGiftCertificate;
use Shopsys\FrameworkBundle\Model\Mail\MailTemplate;
use Shopsys\FrameworkBundle\Model\Mail\MessageData;
use Shopsys\FrameworkBundle\Model\Mail\MessageFactoryInterface;
use Shopsys\FrameworkBundle\Model\Mail\Setting\MailSetting;

class OrderGiftCertificateMail implements MessageFactoryInterface
{
    const GIFT_CERTIFICATE_CODE = '{gift_certificate_code}';
    const ORDER_NUMBER = '{order_number}';

    /**
     * @var \App\Component\Setting\Setting
     */
    private $setting;

    /**
     * @param \App\Component\Setting\Setting $setting
     */
    public function __construct(Setting $setting)
    {
        $this->setting = $setting;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Mail\MailTemplate $template
     * @param \App\Model\Order\GiftCertificate\OrderGiftCertificate $personalData
     * @return \Shopsys\FrameworkBundle\Model\Mail\MessageData
     */
    public function createMessage(MailTemplate $template, $personalData)
    {
        $order = $personalData->getOrder();

        return new MessageData(
            $order->getEmail(),
            $template->getBccEmail(),
            $template->getBody(),
            $template->getSubject(),
            $this->setting->getForDomain(MailSetting::MAIN_ADMIN_MAIL, $order->getDomainId()),
            $this->setting->getForDomain(MailSetting::MAIN_ADMIN_MAIL_NAME, $order->getDomainId()),
            $this->getVariablesReplacementsForBody($personalData)
        );
    }

    /**
     * @param \App\Model\Order\GiftCertificate\OrderGiftCertificate $orderGiftCertificate
     * @return array
     */
    private function getVariablesReplacementsForBody(OrderGiftCertificate $orderGiftCertificate): array
    {
        return [
            self::GIFT_CERTIFICATE_CODE => $orderGiftCertificate->getGiftCertificate()->getCode(),
            self::ORDER_NUMBER => $orderGiftCertificate->getOrder()->getNumber(),
        ];
    }
}
