<?php

declare(strict_types=1);

namespace App\Model\Order\Mail;

use Shopsys\FrameworkBundle\Model\Order\Mail\OrderMail as BaseOrderMail;
use Shopsys\FrameworkBundle\Model\Order\Order;

/**
 * @property \App\Component\Setting\Setting $setting
 * @property \App\Twig\DateTimeFormatterExtension $dateTimeFormatterExtension
 * @method __construct(\App\Component\Setting\Setting $setting, \Shopsys\FrameworkBundle\Component\Router\DomainRouterFactory $domainRouterFactory, \Twig\Environment $twig, \Shopsys\FrameworkBundle\Model\Order\Item\OrderItemPriceCalculation $orderItemPriceCalculation, \Shopsys\FrameworkBundle\Component\Domain\Domain $domain, \Shopsys\FrameworkBundle\Twig\PriceExtension $priceExtension, \App\Twig\DateTimeFormatterExtension $dateTimeFormatterExtension, \Shopsys\FrameworkBundle\Model\Order\OrderUrlGenerator $orderUrlGenerator)
 * @method \Shopsys\FrameworkBundle\Model\Mail\MessageData createMessage(\Shopsys\FrameworkBundle\Model\Mail\MailTemplate $mailTemplate, \App\Model\Order\Order $order)
 * @method static string getMailTemplateNameByStatus(\App\Model\Order\Status\OrderStatus $orderStatus)
 * @method static \Shopsys\FrameworkBundle\Model\Mail\MailTemplate|null findMailTemplateForOrderStatus(\Shopsys\FrameworkBundle\Model\Mail\MailTemplate[] $mailTemplates, \App\Model\Order\Status\OrderStatus $orderStatus)
 * @method array getVariablesReplacementsForSubject(\App\Model\Order\Order $order)
 * @method string getFormattedPrice(\App\Model\Order\Order $order)
 * @method string getFormattedDateTime(\App\Model\Order\Order $order)
 * @method string getBillingAddressHtmlTable(\App\Model\Order\Order $order)
 * @method string getDeliveryAddressHtmlTable(\App\Model\Order\Order $order)
 * @method string getProductsHtmlTable(\App\Model\Order\Order $order)
 * @method string getDomainLocaleByOrder(\App\Model\Order\Order $order)
 */
class OrderMail extends BaseOrderMail
{
    public const VARIABLE_PREPARED_PRODUCTS = '{preparedProducts}';
    public const VARIABLE_TRACKING_NUMBER = '{tracking_number}';
    public const VARIABLE_TRACKING_URL = '{tracking_url}';

    /**
     * @return array
     */
    public function getTemplateVariables(): array
    {
        $templateVariables = parent::getTemplateVariables();

        array_push(
            $templateVariables,
            self::VARIABLE_PREPARED_PRODUCTS,
            self::VARIABLE_TRACKING_NUMBER,
            self::VARIABLE_TRACKING_URL
        );

        return $templateVariables;
    }

    /**
     * @param \App\Model\Order\Order $order
     * @return array
     */
    protected function getVariablesReplacementsForBody(Order $order): array
    {
        $variableReplacements = parent::getVariablesReplacementsForBody($order);

        $variableReplacements[self::VARIABLE_PREPARED_PRODUCTS] = $this->getPreparedProductsHtmlTable($order);
        $variableReplacements[self::VARIABLE_TRACKING_NUMBER] = $order->getTrackingNumber() ?? t('neznámé');
        $variableReplacements[self::VARIABLE_TRACKING_URL] = $order->getTrackingUrl() ?? t('neznámá');

        return $variableReplacements;
    }

    /**
     * @param \App\Model\Order\Order $order
     * @return string
     */
    private function getPreparedProductsHtmlTable(Order $order): string
    {
        $orderItemTotalPricesById = $this->orderItemPriceCalculation->calculateTotalPricesIndexedById($order->getItems());

        return $this->twig->render('Mail/Order/preparedProducts.html.twig', [
            'order' => $order,
            'orderItemTotalPricesById' => $orderItemTotalPricesById,
            'orderLocale' => $this->getDomainLocaleByOrder($order),
        ]);
    }
}
