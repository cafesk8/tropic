<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Mail;

use Shopsys\FrameworkBundle\Model\Order\Mail\OrderMail as BaseOrderMail;
use Shopsys\FrameworkBundle\Model\Order\Order;

class OrderMail extends BaseOrderMail
{
    public const VARIABLE_PREPARED_PRODUCTS = '{preparedProducts}';

    /**
     * @return array
     */
    public function getTemplateVariables(): array
    {
        $templateVariables = parent::getTemplateVariables();

        $templateVariables[] = self::VARIABLE_PREPARED_PRODUCTS;

        return $templateVariables;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Order $order
     * @return array
     */
    protected function getVariablesReplacementsForBody(Order $order): array
    {
        $variableReplacements = parent::getVariablesReplacementsForBody($order);

        $variableReplacements[self::VARIABLE_PREPARED_PRODUCTS] = $this->getPreparedProductsHtmlTable($order);

        return $variableReplacements;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Order $order
     * @return string
     */
    private function getPreparedProductsHtmlTable(Order $order): string
    {
        $orderItemTotalPricesById = $this->orderItemPriceCalculation->calculateTotalPricesIndexedById($order->getItems());

        return $this->twig->render('@ShopsysShop/Mail/Order/preparedProducts.html.twig', [
            'order' => $order,
            'orderItemTotalPricesById' => $orderItemTotalPricesById,
            'orderLocale' => $this->getDomainLocaleByOrder($order),
        ]);
    }
}
