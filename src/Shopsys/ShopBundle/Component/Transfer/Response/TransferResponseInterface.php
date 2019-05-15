<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Transfer\Response;

interface TransferResponseInterface
{
    /**
     * @return \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse
     */
    public function getResponse(): TransferResponse;
}
