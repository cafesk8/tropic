<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\MassAction;

class OrderMassActionData
{
    /**
     * @var string
     */
    public const SELECT_TYPE_CHECKED = 'selectTypeChecked';

    /**
     * @var string
     */
    public const SELECT_TYPE_ALL_RESULTS = 'selectTypeAllResults';

    /**
     * @var string
     */
    public const ACTION_CSV_EXPORT = 'csvExport';

    /**
     * @var string
     */
    public $selectType;

    /**
     * @var string
     */
    public $action;
}
