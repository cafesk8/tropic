<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\MServer;

use App\Component\Transfer\Pohoda\Exception\PohodaMServerException;
use App\Component\Transfer\Pohoda\Xml\PohodaXmlGenerator;

class MServerClient
{
    public const POHODA_STW_INSTANCE_EXPORT_ADDRESSBOOK = 'Export adresáře do Pohody';

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
     * @param string $pohodaMServerUrl
     * @param string $pohodaMServerPort
     * @param string $pohodaMServerLogin
     * @param string $pohodaMServerPassword
     * @param string $pohodaCompanyIco
     * @param \App\Component\Transfer\Pohoda\Xml\PohodaXmlGenerator $pohodaXmlGenerator
     */
    public function __construct(
        string $pohodaMServerUrl,
        string $pohodaMServerPort,
        string $pohodaMServerLogin,
        string $pohodaMServerPassword,
        string $pohodaCompanyIco,
        PohodaXmlGenerator $pohodaXmlGenerator
    ) {
        $this->pohodaXmlGenerator = $pohodaXmlGenerator;

        $this->pohodaMServerUrl = $pohodaMServerUrl;
        $this->pohodaMServerPort = $pohodaMServerPort;
        $this->pohodaMServerLogin = $pohodaMServerLogin;
        $this->pohodaMServerPassword = $pohodaMServerPassword;
        $this->pohodaCompanyIco = $pohodaCompanyIco;
    }

    /**
     * @param string $pohodaStwInstance
     * @param string $xmlData
     * @throws \App\Component\Transfer\Pohoda\Exception\PohodaMServerException
     */
    private function send(string $pohodaStwInstance, string $xmlData)
    {
        $xmlData = $this->prepareEncodedData($xmlData);

        $connectionUrl = $this->pohodaMServerUrl . ':' . $this->pohodaMServerPort . '/xml';

        $pohodaStwAuthorization = base64_encode($this->pohodaMServerLogin . ':' . $this->pohodaMServerPassword);

        $headers = [
            'Content-Type: text/xml',
            'STW-Application: Shopsys',
            'STW-Instance: ' . $this->prepareEncodedData($pohodaStwInstance),
            'STW-Authorization: Basic ' . $pohodaStwAuthorization,
        ];

        $mServerConnection = curl_init($connectionUrl);
        curl_setopt($mServerConnection, CURLOPT_POST, 1);
        curl_setopt($mServerConnection, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($mServerConnection, CURLOPT_POSTFIELDS, "$xmlData");
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
    }

    /**
     * @param string $data
     * @return bool|false|string
     */
    private function prepareEncodedData(string $data)
    {
        return iconv('UTF-8', 'WINDOWS-1250', $data);
    }

    /**
     * @param \App\Component\Transfer\Pohoda\Customer\PohodaCustomer[] $pohodaCustomers
     */
    public function exportAddressBook(array $pohodaCustomers)
    {
        $xmlData = $this->pohodaXmlGenerator->generateXmlRequest(
            'Component/Transfer/Pohoda/Customer/addressBook.xml.twig',
            [
                'pohodaCompanyIco' => $this->pohodaCompanyIco,
                'pohodaCustomers' => $pohodaCustomers,
            ]
        );

        $this->send(
            self::POHODA_STW_INSTANCE_EXPORT_ADDRESSBOOK,
            $xmlData
        );
    }
}
