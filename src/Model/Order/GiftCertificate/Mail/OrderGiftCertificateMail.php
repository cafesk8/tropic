<?php

declare(strict_types=1);

namespace App\Model\Order\GiftCertificate\Mail;

use App\Component\Setting\Setting;
use App\Model\Order\GiftCertificate\OrderGiftCertificate;
use App\Model\Order\GiftCertificate\Pdf\OrderGiftCertificatePdfFacade;
use IntlDateFormatter;
use Shopsys\FrameworkBundle\Component\Localization\DateTimeFormatter;
use Shopsys\FrameworkBundle\Component\Localization\DateTimeFormatterInterface;
use Shopsys\FrameworkBundle\Model\Mail\MailTemplate;
use Shopsys\FrameworkBundle\Model\Mail\MessageData;
use Shopsys\FrameworkBundle\Model\Mail\MessageFactoryInterface;
use Shopsys\FrameworkBundle\Model\Mail\Setting\MailSetting;

class OrderGiftCertificateMail implements MessageFactoryInterface
{
    public const MAIL_TEMPLATE_DEFAULT_NAME = 'gift_certificate';
    public const MAIL_TEMPLATE_ACTIVATED_NAME = 'gift_certificate_activated';

    public const VARIABLE_GIFT_CERTIFICATE_CODE = '{gift_certificate_code}';
    public const VARIABLE_GIFT_CERTIFICATE_CURRENCY = '{gift_certificate_currency}';
    public const VARIABLE_GIFT_CERTIFICATE_VALID_UNTIL = '{gift_certificate_valid_until}';
    public const VARIABLE_GIFT_CERTIFICATE_VALUE = '{gift_certificate_value}';
    public const VARIABLE_ORDER_NUMBER = '{order_number}';

    private Setting $setting;

    private OrderGiftCertificatePdfFacade $orderGiftCertificatePdfFacade;

    private DateTimeFormatter $dateTimeFormatter;

    /**
     * @param \App\Component\Setting\Setting $setting
     * @param \App\Model\Order\GiftCertificate\Pdf\OrderGiftCertificatePdfFacade $orderGiftCertificatePdfFacade
     * @param \Shopsys\FrameworkBundle\Component\Localization\DateTimeFormatter $dateTimeFormatter
     */
    public function __construct(Setting $setting, OrderGiftCertificatePdfFacade $orderGiftCertificatePdfFacade, DateTimeFormatterInterface $dateTimeFormatter)
    {
        $this->setting = $setting;
        $this->orderGiftCertificatePdfFacade = $orderGiftCertificatePdfFacade;
        $this->dateTimeFormatter = $dateTimeFormatter;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Mail\MailTemplate $template
     * @param \App\Model\Order\GiftCertificate\OrderGiftCertificate $orderGiftCertificate
     * @return \Shopsys\FrameworkBundle\Model\Mail\MessageData
     */
    public function createMessage(MailTemplate $template, $orderGiftCertificate)
    {
        $order = $orderGiftCertificate->getOrder();

        return new MessageData(
            $order->getEmail(),
            $template->getBccEmail(),
            $template->getBody(),
            $template->getSubject(),
            $this->setting->getForDomain(MailSetting::MAIN_ADMIN_MAIL, $order->getDomainId()),
            $this->setting->getForDomain(MailSetting::MAIN_ADMIN_MAIL_NAME, $order->getDomainId()),
            $this->getVariablesReplacementsForBody($orderGiftCertificate),
            [],
            $this->orderGiftCertificatePdfFacade->getFiles($orderGiftCertificate)
        );
    }

    /**
     * @param \App\Model\Order\GiftCertificate\OrderGiftCertificate $orderGiftCertificate
     * @return array
     */
    private function getVariablesReplacementsForBody(OrderGiftCertificate $orderGiftCertificate): array
    {
        return [
            self::VARIABLE_GIFT_CERTIFICATE_CODE => $orderGiftCertificate->getGiftCertificate()->getCode(),
            self::VARIABLE_GIFT_CERTIFICATE_CURRENCY => $orderGiftCertificate->getOrder()->getCurrency()->getCode(),
            self::VARIABLE_GIFT_CERTIFICATE_VALID_UNTIL => $this->dateTimeFormatter->format(
                $orderGiftCertificate->getGiftCertificate()->getValidTo(),
                IntlDateFormatter::MEDIUM,
                IntlDateFormatter::MEDIUM,
                'cs'
            ),
            self::VARIABLE_GIFT_CERTIFICATE_VALUE => (string)round((float)$orderGiftCertificate->getGiftCertificate()->getCertificateValue()->getAmount(), 2),
            self::VARIABLE_ORDER_NUMBER => $orderGiftCertificate->getOrder()->getNumber(),
        ];
    }

    /**
     * @return string[]
     */
    public function getTemplateVariables(): array
    {
        return [
            self::VARIABLE_GIFT_CERTIFICATE_CODE,
            self::VARIABLE_GIFT_CERTIFICATE_CURRENCY,
            self::VARIABLE_GIFT_CERTIFICATE_VALID_UNTIL,
            self::VARIABLE_GIFT_CERTIFICATE_VALUE,
            self::VARIABLE_ORDER_NUMBER,
        ];
    }
}
