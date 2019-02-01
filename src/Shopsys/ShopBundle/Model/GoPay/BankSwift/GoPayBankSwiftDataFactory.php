<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Model\GoPay\BankSwift;

class GoPayBankSwiftDataFactory
{
    /**
     * @return \Shopsys\ShopBundle\Model\GoPay\BankSwift\GoPayBankSwiftData
     */
    public function create(): GoPayBankSwiftData
    {
        return new GoPayBankSwiftData();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\GoPay\BankSwift\GoPayBankSwift $goPayBankSwift
     * @return \Shopsys\ShopBundle\Model\GoPay\BankSwift\GoPayBankSwiftData
     */
    public function createFromGoPayBankSwift(GoPayBankSwift $goPayBankSwift): GoPayBankSwiftData
    {
        $goPayBankSwiftData = $this->create();

        $goPayBankSwiftData->swift = $goPayBankSwift->getSwift();
        $goPayBankSwiftData->goPayPaymentMethod = $goPayBankSwift->getGoPayPaymentMethod();
        $goPayBankSwiftData->name = $goPayBankSwift->getName();
        $goPayBankSwiftData->imageNormalUrl = $goPayBankSwift->getImageNormalUrl();
        $goPayBankSwiftData->imageLargeUrl = $goPayBankSwift->getImageLargeUrl();
        $goPayBankSwiftData->isOnline = $goPayBankSwift->isOnline();

        return $goPayBankSwiftData;
    }
}
