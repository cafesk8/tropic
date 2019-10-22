<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Command\Migrations\DataProvider;

use DOMDocument;

class MigrateProductSlovakLegacyUrlsDataProvider
{
    private const DATA_URL = 'http://www.bushmanoriginal.sk/zzz_utility/get_product_list.php';
    private const LEGACY_BASE_URL = 'https://www.bushmanoriginal.sk/';

    /**
     * @return string[][]
     */
    public static function getLegacySlugsIndexedByCatnum(): array
    {
        $legacySlugsIndexedByCatnum = [];
        $htmlTableWithData = file_get_contents(self::DATA_URL);
        $dom = new DOMDocument();
        @$dom->loadHTML($htmlTableWithData);
        $dom->preserveWhiteSpace = false;
        $rows = $dom->getElementsByTagName('tr');
        foreach ($rows as $row) {
            $columns = $row->getElementsByTagName('td');
            $catnum = preg_replace('#\D#', '', $columns[1]->textContent);
            $slug = str_replace(self::LEGACY_BASE_URL, '', $columns[2]->textContent);
            $legacySlugsIndexedByCatnum[$catnum][] = $slug;
        }

        return $legacySlugsIndexedByCatnum;
    }
}
