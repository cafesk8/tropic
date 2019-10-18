<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transfer\Issue;

use Shopsys\ShopBundle\Model\Transfer\Transfer;

class TransferIssueData
{
    /**
     * @var \Shopsys\ShopBundle\Model\Transfer\Transfer
     */
    public $transfer;

    /**
     * @var string
     */
    public $message;

    /**
     * @param \Shopsys\ShopBundle\Model\Transfer\Transfer $transfer
     * @param string $message
     */
    public function __construct(Transfer $transfer, string $message)
    {
        $this->transfer = $transfer;
        $this->message = $message;
    }
}
