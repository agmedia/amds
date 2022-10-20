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
            $temps = collect($res->rows)->where('price', '!=', 0)->take(10);
        }

        $arr = [];

        foreach ($temps as $temp) {
            $model = substr($temp['sku'], 0, strrpos($temp['sku'], '-'));
            $arr[$model] = $temp['special'];
        }

        foreach ($arr as $model => $special) {
            $db->query("UPDATE " . DB_PREFIX . "product SET price_ponuda = '" . $special . "' WHERE model = '" . $model . "'");
        }

        return true;
    }
}