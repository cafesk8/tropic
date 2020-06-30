<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Xml;

use App\Component\Transfer\Pohoda\Response\PohodaAddressBookResponse;
use App\Component\Transfer\Pohoda\Response\PohodaOrderResponse;
use App\Component\Transfer\Pohoda\Response\PohodaResponse;
use SimpleXMLElement;

class PohodaXmlResponseParser
{
    /**
     * @param string $mServerResponse
     * @return \App\Component\Transfer\Pohoda\Response\PohodaAddressBookResponse[]
     */
    public function parseAddressBookResponses(string $mServerResponse): array
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

    /**
     * @param string $mServerResponse
     * @return \App\Component\Transfer\Pohoda\Response\PohodaOrderResponse[]
     */
    public function parseOrderResponses(string $mServerResponse): array
    {
        $mServerResponseXml = new SimpleXMLElement($mServerResponse);
        $ns = $mServerResponseXml->getDocNamespaces();
        $pohodaOrderResponses = [];
        foreach ($mServerResponseXml->children($ns['rsp']) as $responsePackItem) {
            $pohodaOrderResponse = new PohodaOrderResponse();
            $responsePackItemAttributes = $responsePackItem->attributes();
            $pohodaOrderResponse->responsePackItemId = (string)$responsePackItemAttributes['id'];
            $pohodaOrderResponse->responsePackItemState = (string)$responsePackItemAttributes['state'];
            if (isset($responsePackItemAttributes['note'])) {
                $pohodaOrderResponse->responsePackItemNote = $responsePackItemAttributes['note'];
            }

            if ($pohodaOrderResponse->responsePackItemState === PohodaResponse::POHODA_XML_RESPONSE_ITEM_STATE_OK) {
                $orderResponse = $responsePackItem->children('ord', true)->orderResponse;
                $pohodaOrderResponse->producedDetailId = (int)$orderResponse->children('rdc', true)->producedDetails->id;
            }

            $pohodaOrderResponses[$pohodaOrderResponse->responsePackItemId] = $pohodaOrderResponse;
        }

        return $pohodaOrderResponses;
    }
}
