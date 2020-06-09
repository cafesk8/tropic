<?php

declare(strict_types=1);

namespace Tests\App\Unit\Component\Cofidis;

use App\Component\Cofidis\CofidisResponseParser;
use PHPUnit\Framework\TestCase;

class CofidisResponseParserTest extends TestCase
{
    /**
     * @param string $data
     * @param array $expectedResult
     * @dataProvider getTestCases
     */
    public function testParseCofidisResponseAsArray(string $data, array $expectedResult): void
    {
        $this->assertSame($expectedResult, CofidisResponseParser::parseCofidisResponseAsArray($data));
    }

    /**
     * @return array
     */
    public function getTestCases(): array
    {
        return [
            'empty' => [
                'data' => '',
                'expectedResult' => [],
            ],
            'validData' => [
                'data' => file_get_contents(__DIR__ . '/valid_data.txt'),
                'expectedResult' => [
                    'cache-control' => 'private',
                    'access-control-allow-origin' => '*',
                    'access-control-expose-headers' => 'OPERATION, SELECTED_PRODUCT, SELECTED_INSTALLMENT, CURRENCY, AMOUNT, DEPOSIT, DEPOSIT_MIN, DEPOSIT_MAX, LOAN_AMOUNT, NUM_INSTALLMENTS, RPSN, INTEREST, MONTHLY_PAYMENTS, TOTAL_PAYMENT, MIN_MONTHLY_PAYMENTS, PRODUCTS, INSTALLMENTS, URL, TRANSACTION_ID, MERCHANT_ID, ADDITIONAL_DATA, IPLATBA_DEMAND_ID, STATUS, ERROR_CODE, ERROR_TEXT',
                    'error_code' => '0',
                    'url' => 'https%3a%2f%2ftest.gw1.iplatba.cz%2ftampon%3fguid%3d51f863d7-9bc1-dbd5-446c-a012b4503936',
                    'operation' => 'START_LOAN_DEMAND',
                    'date' => 'Thu, 11 Jun 2020 09:04:00 GMT',
                    'content-length' => '0',
                ],
            ],
            'randomData' => [
                'data' => 'random data',
                'expectedResult' => [],
            ],
            'randomDataContainColon' => [
                'data' => 'random: data',
                'expectedResult' => ['random' => 'data'],
            ],
            'randomDataContainTwoColons' => [
                'data' => 'random:: data',
                'expectedResult' => [],
            ],
        ];
    }
}
