<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Admin;

use Shopsys\FrameworkBundle\Controller\Admin\MailController as BaseMailController;
use Shopsys\ShopBundle\Model\Order\Mail\OrderMail;

class MailController extends BaseMailController
{
    /**
     * @return array
     */
    protected function getOrderStatusVariablesLabels(): array
    {
        $orderStatusVariablesLables = parent::getOrderStatusVariablesLabels();

        $orderStatusVariablesLables[OrderMail::VARIABLE_PREPARED_PRODUCTS] =
            t('Seznam již dostupného zboží v objednávce (název, dostupné množství, cena za jednotku s DPH, celková cena za položku s DPH)');
        $orderStatusVariablesLables[OrderMail::VARIABLE_TRACKING_URL] = t('Odkaz pro sledování zásilky');
        $orderStatusVariablesLables[OrderMail::VARIABLE_TRACKING_NUMBER] = t('Číslo pro sledování zásilky');

        return $orderStatusVariablesLables;
    }
}
