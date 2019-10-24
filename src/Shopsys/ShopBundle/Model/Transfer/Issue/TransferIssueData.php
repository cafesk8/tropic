<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transfer\Issue;

class TransferIssueData
{
    /**
     * @var string
     */
    public $transferIdentifier;

    /**
     * @var string
     */
    public $message;

    /**
     * @param string $transferIdentifier
     * @param string $message
     */
    public function __construct(string $transferIdentifier, string $message)
    {
        $this->transferIdentifier = $transferIdentifier;
        $this->message = $message;
    }
}
