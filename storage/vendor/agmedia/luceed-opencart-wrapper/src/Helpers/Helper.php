<?php

namespace Agmedia\LuceedOpencartWrapper\Helpers;

use Agmedia\Helpers\Log;
use Agmedia\Helpers\Database;

/**
 * Class Helper
 * @package Agmedia\LuceedOpencartWrapper\Helpers
 */
class Helper
{

    /**
     * @return bool
     * @throws \Exception
     */
    public static function overwritePricesAndSpecialsFromTempTable(): bool
    {
        $db  = new Database(DB_DATABASE);
        $res = $db->query("SELECT * FROM temp;");

        if ($res->num_rows) {
            $temps = collect($res->rows);
        }

        $arr = [];

        foreach ($temps as $temp) {
            //$model       = substr($temp['sku'], 0, strrpos($temp['sku'], '-'));
            $arr[$temp['sku']] = [
                'price'        => $temp['special'],
                'price_ponuda' => $temp['price']
            ];
        }

        foreach ($arr as $model => $item) {
            $db->query("UPDATE " . DB_PREFIX . "product SET price_ponuda = '" . $item['price_ponuda'] . "', price = '" . $item['price'] . "' WHERE model = '" . $model . "'");
        }

        return true;
    }
}