<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\MServer;

use App\Component\Transfer\Pohoda\Backup\PohodaTransferBackup;
use App\Component\Transfer\Pohoda\Customer\PohodaCustomerValidator;
use App\Component\Transfer\Pohoda\Exception\PohodaInvalidDataException;
use App\Component\Transfer\Pohoda\Exception\PohodaMServerException;
use App\Component\Transfer\Pohoda\Order\PohodaOrderValidator;
use App\Component\Transfer\Pohoda\Xml\PohodaXmlGenerator;
use App\Component\Transfer\Pohoda\Xml\PohodaXmlResponseParser;
use League\Flysystem\FilesystemInterface;

class MServerClient
{
    public const POHODA_STW_INSTANCE_EXPORT_ADDRESSBOOK = 'Export adresáře do Pohody';
    public const POHODA_STW_INSTANCE_EXPORT_ORDERS = 'Export objednávek do Pohody';
    public const POHODA_STW_INSTANCE_IMPORT_IMAGE = 'Import obrázku z Pohody';

    public const BACKUP_IDENTIFIER_EXPORT_ADDRESSBOOK = 'exportAddressBook';
    public const BACKUP_IDENTIFIER_EXPORT_ORDERS = 'exportOrders';

    /**
     * @var \App\Component\Transfer\Pohoda\Xml\PohodaXmlGenerator
     */
    private $pohodaXmlGenerator;

    /**
     * @var string
     */
    private $pohodaMServerUrl;

    /**
     * @var string
     */
    private $pohodaMServerPort;

    /**
     * @var string
     */
    private $pohodaMServerLogin;

    /**
     * @var string
     */
    private $pohodaMServerPassword;

    /**
     * @var string
     */
    private $pohodaCompanyIco;

    /**
     * @var \App\Component\Transfer\Pohoda\Xml\PohodaXmlResponseParser
     */
    private $pohodaXmlResponseParser;

    /**
     * @var \App\Component\Transfer\Pohoda\Customer\PohodaCustomerValidator
     */
    private $pohodaCustomerValidator;

    /**
     * @var \App\Component\Transfer\Pohoda\Order\PohodaOrderValidator
     */
    private $pohodaOrderValidator;

    private PohodaTransferBackup $pohodaTransferBackup;

    /**
     * @param string $pohodaMServerUrl
     * @param string $pohodaMServerPort
     * @param string $pohodaMServerLogin
     * @param string $pohodaMServerPassword
     * @param string $pohodaCompanyIco
     * @param \App\Component\Transfer\Pohoda\Xml\PohodaXmlGenerator $pohodaXmlGenerator
     * @param \App\Component\Transfer\Pohoda\Xml\PohodaXmlResponseParser $pohodaXmlResponseParser
     * @param \App\Component\Transfer\Pohoda\Customer\PohodaCustomerValidator $pohodaCustomerValidator
     * @param \App\Component\Transfer\Pohoda\Order\PohodaOrderValidator $pohodaOrderValidator
     * @param \App\Component\Transfer\Pohoda\Backup\PohodaTransferBackup $pohodaTransferBackup
     */
    public function __construct(
        string $pohodaMServerUrl,
        string $pohodaMServerPort,
        string $pohodaMServerLogin,
        string $pohodaMServerPassword,
        string $pohodaCompanyIco,
        PohodaXmlGenerator $pohodaXmlGenerator,
        PohodaXmlResponseParser $pohodaXmlResponseParser,
        PohodaCustomerValidator $pohodaCustomerValidator,
        PohodaOrderValidator $pohodaOrderValidator,
        PohodaTransferBackup $pohodaTransferBackup
    ) {
        $this->pohodaXmlGenerator = $pohodaXmlGenerator;

        $this->pohodaMServerUrl = $pohodaMServerUrl;
        $this->pohodaMServerPort = $pohodaMServerPort;
        $this->pohodaMServerLogin = $pohodaMServerLogin;
        $this->pohodaMServerPassword = $pohodaMServerPassword;
        $this->pohodaCompanyIco = $pohodaCompanyIco;
        $this->pohodaXmlResponseParser = $pohodaXmlResponseParser;
        $this->pohodaCustomerValidator = $pohodaCustomerValidator;
        $this->pohodaOrderValidator = $pohodaOrderValidator;
        $this->pohodaTransferBackup = $pohodaTransferBackup;
    }

    /**
     * @param string $pohodaStwInstance
     * @param string $connectionPath
     * @param string|null $postXmlData
     * @param string|null $xmlBackupIdentifier
     * @throws \App\Component\Transfer\Pohoda\Exception\PohodaMServerException
     * @return string
     */
    private function send(
        string $pohodaStwInstance,
        string $connectionPath,
        ?string $postXmlData = null,
        ?string $xmlBackupIdentifier = null
    ): string {
        $connectionUrl = $this->pohodaMServerUrl . ':' . $this->pohodaMServerPort . $connectionPath;

        $pohodaStwAuthorization = base64_encode($this->pohodaMServerLogin . ':' . $this->pohodaMServerPassword);

        $headers = [
            'Content-Type: text/xml',
            'STW-Application: Shopsys',
            'STW-Instance: ' . $this->pohodaXmlGenerator->prepareEncodedData($pohodaStwInstance),
            'STW-Authorization: Basic ' . $pohodaStwAuthorization,
        ];

        $transferTimestamp = time();
        $this->pohodaTransferBackup->backupXml($xmlBackupIdentifier, $postXmlData, $transferTimestamp, 'request');

        $mServerConnection = curl_init($connectionUrl);
        curl_setopt($mServerConnection, CURLOPT_HTTPHEADER, $headers);
        if ($postXmlData !== null) {
            curl_setopt($mServerConnection, CURLOPT_POST, 1);
            curl_setopt($mServerConnection, CURLOPT_POSTFIELDS, "$postXmlData");
        } else {
            curl_setopt($mServerConnection, CURLOPT_HTTPGET, 1);
        }
        curl_setopt($mServerConnection, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($mServerConnection, CURLOPT_USERPWD, $this->pohodaMServerLogin . ':' . $this->pohodaMServerPassword);

        $mServerResult = curl_exec($mServerConnection);

        $mServerResponseHttpCode = curl_getinfo($mServerConnection, CURLINFO_HTTP_CODE);

        if ($mServerResult === false) {
            $errorMessage = curl_error($mServerConnection) . ', Kód: ' . curl_errno($mServerConnection);

            throw new PohodaMServerException($errorMessage);
        } elseif ($mServerResponseHttpCode !== 200) {
            $errorMessage = 'HTTP kód odpovědi ' . $mServerResponseHttpCode;

            throw new PohodaMServerException($errorMessage);
        }

        curl_close($mServerConnection);

        $this->pohodaTransferBackup->backupXml($xmlBackupIdentifier, $mServerResult, $transferTimestamp, 'response');

        return $mServerResult;
    }

    /**
     * @param \App\Component\Transfer\Pohoda\Customer\PohodaCustomer[] $pohodaCustomers
     * @return \App\Component\Transfer\Pohoda\Customer\PohodaCustomer[]
     */
    public function exportAddressBook(array $pohodaCustomers): array
    {
        $validPohodaCustomers = [];

        foreach ($pohodaCustomers as $pohodaCustomer) {
            try {
                $this->pohodaCustomerValidator->validate($pohodaCustomer);
            } catch (PohodaInvalidDataException $exc) {
                continue;
            }

            $validPohodaCustomers[] = $pohodaCustomer;
        }

        $xmlData = $this->pohodaXmlGenerator->generateXmlRequest(
            'Component/Transfer/Pohoda/Customer/addressBook.xml.twig',
            [
                'pohodaCompanyIco' => $this->pohodaCompanyIco,
                'pohodaCustomers' => $validPohodaCustomers,
            ]
        );

        $mServerResponse = $this->send(
            self::POHODA_STW_INSTANCE_EXPORT_ADDRESSBOOK,
            '/xml',
            $xmlData,
            self::BACKUP_IDENTIFIER_EXPORT_ADDRESSBOOK
        );

        $addressBookResponses = $this->pohodaXmlResponseParser->parseAddressBookResponses($mServerResponse);

        foreach ($pohodaCustomers as $pohodaCustomer) {
            if (array_key_exists($pohodaCustomer->dataPackItemId, $addressBookResponses)) {
                $pohodaCustomer->addressBookResponse = $addressBookResponses[$pohodaCustomer->dataPackItemId];
            }
        }

        return $pohodaCustomers;
    }

    /**
     * @param \App\Component\Transfer\Pohoda\Order\PohodaOrder[] $pohodaOrders
     * @return \App\Component\Transfer\Pohoda\Order\PohodaOrder[]
     */
    public function exportOrders(array $pohodaOrders): array
    {
        $validPohodaOrders = [];
        foreach ($pohodaOrders as $pohodaOrder) {
            // The data should be valid. Validation is here only for safety reasons.
            try {
                $this->pohodaOrderValidator->validate($pohodaOrder);
            } catch (PohodaInvalidDataException $exc) {
                continue;
            }

            $validPohodaOrders[] = $pohodaOrder;
        }

        $xmlData = $this->pohodaXmlGenerator->generateXmlRequest(
            'Component/Transfer/Pohoda/Order/orders.xml.twig',
            [
                'pohodaCompanyIco' => $this->pohodaCompanyIco,
                'pohodaOrders' => $validPohodaOrders,
            ]
        );

        $mServerResponse = $this->send(
            self::POHODA_STW_INSTANCE_EXPORT_ORDERS,
            '/xml',
            $xmlData,
            self::BACKUP_IDENTIFIER_EXPORT_ORDERS
        );

        $orderResponses = $this->pohodaXmlResponseParser->parseOrderResponses($mServerResponse);

        foreach ($pohodaOrders as $pohodaOrder) {
            if (array_key_exists($pohodaOrder->dataPackItemId, $orderResponses)) {
                $pohodaOrder->orderResponse = $orderResponses[$pohodaOrder->dataPackItemId];
            }
        }

        return $pohodaOrders;
    }

    /**
     * @param string $connectionPath
     * @return string
     */
    public function getImage(string $connectionPath): string
    {
        return $this->send(
            self::POHODA_STW_INSTANCE_IMPORT_IMAGE,
            $connectionPath
        );
    }
}
