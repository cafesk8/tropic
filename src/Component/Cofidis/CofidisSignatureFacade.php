<?php

declare(strict_types=1);

namespace App\Component\Cofidis;

class CofidisSignatureFacade
{
    /**
     * @param array $paymentData
     * @param string $inboundPassword
     * @return string
     */
    public function getSignature(array $paymentData, string $inboundPassword): string
    {
        $hashedData = $this->getHashedData($paymentData);

        return $this->encrypt($hashedData, $inboundPassword);
    }

    /**
     * @param array $data
     * @return string
     */
    private function getHashedData(array $data): string
    {
        $chainedData = implode('|', $data);
        $chainedDataInUtf16 = mb_convert_encoding($chainedData, 'UTF-16LE', 'UTF-8');
        $hashedData = sha1($chainedDataInUtf16, true);
        $base64Data = base64_encode($hashedData);

        return bin2hex(mb_convert_encoding($base64Data, 'UTF-16LE'));
    }

    /**
     * @param string $hashedData
     * @param string $inboundPassword
     * @return string
     */
    private function encrypt(string $hashedData, string $inboundPassword): string
    {
        $inboundPasswordInUtf16 = mb_convert_encoding($inboundPassword, 'UTF-16LE');
        $inboundPassword = md5(utf8_encode($inboundPasswordInUtf16), true);
        $inboundPassword .= substr($inboundPassword, 0, 8);
        $hashedDataInUtf16 = mb_convert_encoding($hashedData, 'UTF-16LE');
        $encryptedHashedData = openssl_encrypt($hashedDataInUtf16, 'des-ede3', $inboundPassword, OPENSSL_RAW_DATA);
        $encryptedHashedDataInHexadecimal = bin2hex(base64_encode($encryptedHashedData));

        $signature = '';
        for ($i = 0, $signatureLength = mb_strlen($encryptedHashedDataInHexadecimal, 'ASCII'); $i < $signatureLength; $i += 2) {
            $signature .= mb_substr($encryptedHashedDataInHexadecimal, $i, 2) . '00';
        }

        return $signature;
    }
}
