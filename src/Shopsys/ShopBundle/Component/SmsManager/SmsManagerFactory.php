<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\SmsManager;

use SimPod\SmsManager\ApiSmsManager;
use SimPod\SmsManager\SmsManager;

class SmsManagerFactory
{
    /**
     * @var \SimPod\SmsManager\SmsManager
     */
    private $smsManager;

    /**
     * @param string $apiKey
     * @param \SimPod\SmsManager\ApiSmsManager $smsManager
     */
    public function __construct(string $apiKey, ApiSmsManager $smsManager)
    {
        $this->smsManager = $smsManager;
        $this->smsManager->setApiKey($apiKey);
    }

    /**
     * @return \SimPod\SmsManager\SmsManager
     */
    public function getManager(): SmsManager
    {
        return $this->smsManager;
    }
}
