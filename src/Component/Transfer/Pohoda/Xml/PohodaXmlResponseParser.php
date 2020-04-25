<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Xml;

use App\Component\Transfer\Pohoda\Response\PohodaAddressBookResponse;
use App\Component\Transfer\Pohoda\Response\PohodaResponse;
use SimpleXMLElement;

class PohodaXmlResponseParser
{
    /**
     * @param string $mServerResponse
     * @return \App\Component\Transfer\Pohoda\Response\PohodaAddressBookResponse[]
     */
    public function parseAddressBookResponses(string $mServerResponse)
    {
        $mServerResponseXml = new SimpleXMLElement($mServerResponse);

        $ns = $mServerResponseXml->getDocNamespaces();

        $pohodaAddressBookResponses = [];

        foreach ($mServerResponseXml->children($ns['rsp']) as $responsePackItem) {
            $pohodaAddressBookResponse = new PohodaAddressBookResponse();

            $responsePackItemAttributes = $responsePackItem->attributes();

            $pohodaAddressBookResponse->responsePackItemId = (string)$responsePackItemAttributes['id'];

            $pohodaAddressBookResponse->responsePackItemState = (string)$responsePackItemAttributes['state'];

            if (isset($responsePackItemAttributes['note'])) {
                $pohodaAddressBookResponse->responsePackItemNote = $responsePackItemAttributes['note'];
            }

            if ($pohodaAddressBookResponse->responsePackItemState === PohodaResponse::POHODA_XML_RESPONSE_ITEM_STATE_OK) {
                $addressbookResponse = $responsePackItem->children('adb', true)->addressbookResponse;

                $pohodaAddressBookResponse->producedDetailId = (int)$addressbookResponse->children('rdc', true)->producedDetails->id;
            }

            $pohodaAddressBookResponses[$pohodaAddressBookResponse->responsePackItemId] = $pohodaAddressBookResponse;
        }

        return $pohodaAddressBookResponses;
    }
}
