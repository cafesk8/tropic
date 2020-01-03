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
     * @var string|null
     */
    public $context;

    /**
     * @var int|null
     */
    public $groupId;

    /**
     * @param string $transferIdentifier
     * @param string $message
     * @param string $groupId
     * @param string|null $context
     */
    public function __construct(string $transferIdentifier, string $message, string $groupId, ?string $context)
    {
        $this->transferIdentifier = $transferIdentifier;
        $this->message = $message;
        $this->context = $context;
        $this->groupId = $groupId;
    }
}
