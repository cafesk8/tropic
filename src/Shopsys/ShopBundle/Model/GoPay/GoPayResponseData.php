<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\GoPay;

use GoPay\Http\Response;

class GoPayResponseData
{
    /**
     * @var \GoPay\Http\Response
     */
    public $response;

    /**
     * @var \Shopsys\ShopBundle\Model\GoPay\GoPayTransaction
     */
    public $goPayTransaction;

    /**
     * @param \GoPay\Http\Response $response
     * @param \Shopsys\ShopBundle\Model\GoPay\GoPayTransaction $goPayTransaction
     */
    public function __construct(Response $response, GoPayTransaction $goPayTransaction)
    {
        $this->response = $response;
        $this->goPayTransaction = $goPayTransaction;
    }
}
