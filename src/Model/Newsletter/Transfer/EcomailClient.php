<?php

declare(strict_types=1);

namespace App\Model\Newsletter\Transfer;

use App\Component\Transfer\Logger\TransferLoggerFactory;
use App\Model\Customer\User\CustomerUserFacade;
use App\Model\Newsletter\NewsletterSubscriber;
use App\Model\Order\OrderFacade;
use Shopsys\FrameworkBundle\Model\Order\Exception\OrderNotFoundException;

class EcomailClient
{
    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $listId;

    /**
     * @var \App\Model\Customer\User\CustomerUserFacade
     */
    private $customerUserFacade;

    /**
     * @var \App\Model\Order\OrderFacade
     */
    private $orderFacade;

    /**
     * @var \App\Component\Transfer\Logger\TransferLogger
     */
    private $logger;

    /**
     * @param string $apiKey
     * @param int $listId
     * @param \App\Model\Customer\User\CustomerUserFacade $customerUserFacade
     * @param \App\Model\Order\OrderFacade $orderFacade
     * @param \App\Component\Transfer\Logger\TransferLoggerFactory $transferLoggerFactory
     */
    public function __construct(string $apiKey, int $listId, CustomerUserFacade $customerUserFacade, OrderFacade $orderFacade, TransferLoggerFactory $transferLoggerFactory)
    {
        $this->apiKey = $apiKey;
        $this->listId = (string)$listId;
        $this->customerUserFacade = $customerUserFacade;
        $this->orderFacade = $orderFacade;
        $this->logger = $transferLoggerFactory->getTransferLoggerByIdentifier(EcomailExportCronModule::TRANSFER_IDENTIFIER);
    }

    /**
     * @param \App\Model\Newsletter\NewsletterSubscriber $newsletterSubscriber
     * @return bool
     */
    public function addSubscriber(NewsletterSubscriber $newsletterSubscriber): bool
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'http://api2.ecomailapp.cz/lists/' . $this->listId . '/subscribe');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'subscriber_data' => $this->getSubscriberData($newsletterSubscriber),
            'resubscribe' => false,
        ]));

        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'key: ' . $this->apiKey]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $this->logger->addError('Export of a customer to Ecomail failed', [
                'id' => $newsletterSubscriber->getId(),
                'email' => $newsletterSubscriber->getEmail(),
                'message' => $response,
            ]);
        }

        return (int)$httpCode === 200;
    }

    /**
     * @param \App\Model\Newsletter\NewsletterSubscriber $newsletterSubscriber
     * @return array
     */
    private function getSubscriberData(NewsletterSubscriber $newsletterSubscriber): array
    {
        $subscriberData = ['email' => $newsletterSubscriber->getEmail()];
        $customerUser = $this->customerUserFacade->findCustomerUserByEmailAndDomain($newsletterSubscriber->getEmail(), $newsletterSubscriber->getDomainId());

        if ($customerUser !== null) {
            $subscriberData['name'] = $customerUser->getFirstName();
            $subscriberData['surname'] = $customerUser->getLastName();
            $subscriberData['phone'] = $customerUser->getTelephone();

            $address = $customerUser->getDefaultDeliveryAddress();

            if ($address !== null) {
                $subscriberData['street'] = $address->getStreet();
                $subscriberData['city'] = $address->getCity();
                $subscriberData['zip'] = $address->getPostcode();
                $subscriberData['country'] = $address->getCountry()->getCode();
            }
        } else {
            try {
                $order = $this->orderFacade->getNewestByEmailAndDomain($newsletterSubscriber->getEmail(), $newsletterSubscriber->getDomainId());
                $subscriberData['name'] = $order->getFirstName();
                $subscriberData['surname'] = $order->getLastName();
                $subscriberData['phone'] = $order->getTelephone() ?? $order->getDeliveryTelephone();
                $subscriberData['street'] = $order->getStreet() ?? $order->getDeliveryStreet();
                $subscriberData['city'] = $order->getCity() ?? $order->getDeliveryCity();
                $subscriberData['zip'] = $order->getPostcode() ?? $order->getDeliveryPostcode();
                $subscriberData['country'] = $order->getCountry()->getCode() ?? $order->getDeliveryCountry()->getCode();
            } catch (OrderNotFoundException $exception) {
            }
        }

        return $subscriberData;
    }
}
