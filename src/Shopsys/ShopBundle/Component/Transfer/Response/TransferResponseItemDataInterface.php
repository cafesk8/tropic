<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Transfer\Response;

interface TransferResponseItemDataInterface
{
    /**
     * @return string
     */
    public function getDataIdentifier(): string;
}
