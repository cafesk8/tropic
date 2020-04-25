<?php

declare(strict_types=1);

namespace App\Model\Customer\Transfer;

use App\Component\Transfer\Pohoda\Customer\PohodaAddress;
use App\Component\Transfer\Pohoda\Customer\PohodaCustomer;
use App\Model\Customer\User\CustomerUser;

class PohodaCustomerMapper
{
    /**
     * @param \App\Model\Customer\User\CustomerUser $customerUser
     * @return \App\Component\Transfer\Pohoda\Customer\PohodaCustomer
     */
    public function mapCustomerUserToPohodaCustomer(CustomerUser $customerUser)
    {
        $pohodaCustomer = new PohodaCustomer();
        $pohodaCustomer->dataPackItemId = 'adr' . time() . '-' . $customerUser->getId();
        $pohodaCustomer->eshopId = $customerUser->getId();
        $pohodaCustomer->priceIds = $customerUser->getPricingGroup()->getPohodaIdent();

        $pohodaBillingAddress = new PohodaAddress();
        $pohodaBillingAddress->company = $customerUser->getCustomer()->getBillingAddress()->getCompanyName();
        $pohodaBillingAddress->ico = $customerUser->getCustomer()->getBillingAddress()->getCompanyNumber();
        $pohodaBillingAddress->dic = $customerUser->getCustomer()->getBillingAddress()->getCompanyTaxNumber();
        $pohodaBillingAddress->name = $customerUser->getFirstName() . ' ' . $customerUser->getLastName();
        $pohodaBillingAddress->city = $customerUser->getCustomer()->getBillingAddress()->getCity();
        $pohodaBillingAddress->street = $customerUser->getCustomer()->getBillingAddress()->getStreet();
        $pohodaBillingAddress->zip = $customerUser->getCustomer()->getBillingAddress()->getPostcode();
        $pohodaBillingAddress->country = $customerUser->getCustomer()->getBillingAddress()->getCountry()->getCode();
        $pohodaBillingAddress->email = $customerUser->getEmail();
        $pohodaBillingAddress->phone = $customerUser->getTelephone();

        $pohodaCustomer->address = $pohodaBillingAddress;

        if ($customerUser->getDefaultDeliveryAddress() !== null) {
            $pohodaDeliveryAddress = new PohodaAddress();
            $pohodaDeliveryAddress->company = $customerUser->getDefaultDeliveryAddress()->getCompanyName();
            $defaultDeliveryAddressNameParts =
                [
                    $customerUser->getDefaultDeliveryAddress()->getFirstName(),
                    $customerUser->getDefaultDeliveryAddress()->getLastName(),
                ];
            $pohodaDeliveryAddress->name = implode(' ', $defaultDeliveryAddressNameParts);
            $pohodaDeliveryAddress->city = $customerUser->getDefaultDeliveryAddress()->getCity();
            $pohodaDeliveryAddress->street = $customerUser->getDefaultDeliveryAddress()->getStreet();
            $pohodaDeliveryAddress->zip = $customerUser->getDefaultDeliveryAddress()->getPostcode();
            $pohodaDeliveryAddress->country = $customerUser->getDefaultDeliveryAddress()->getCountry()->getCode();

            $pohodaCustomer->shipToAddress = $pohodaDeliveryAddress;
        }

        //TODO Doplnit ostatni atributy a odstranit email z adres
        return $pohodaCustomer;
    }
}
