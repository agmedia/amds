<?php

namespace Agmedia\LuceedOpencartWrapper\Helpers;

use Agmedia\Helpers\Log;
use Agmedia\Helpers\Database;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Category;

/**
 * Class Helper
 * @package Agmedia\LuceedOpencartWrapper\Helpers
 */
class CategoryHelper
{


    /**
     * @param string $name
     * @param        $parent_id
     *
     * @return bool|\stdClass
     * @throws \Exception
     */
    public static function getCategory(string $name, $parent_id)
    {
        $db = new Database(DB_DATABASE);

        return $db->query("SELECT * FROM oc_category c LEFT JOIN oc_category_description cd ON c.category_id = cd.category_id WHERE c.parent_id = " . $parent_id . " AND cd.name = '" . $name . "'");
    }


    /**
     * @param $data
     *
     * @return array
     */
    public static function setCategory($data): array
    {
        $category = [];

        $category['grupa_artikla'] = $data->atribut_uid;
        $category['naziv'] = $data->vrijednost;

        return $category;
    }


    /**
     * @param array $data
     *
     * @return \stdClass
     */
    public static function setGenderCategory(array $data): \stdClass
    {
        $response = new \stdClass();
        $response->vrijednost = 'ŽENE';
        $response->atribut_uid = '2';

        if ($data['spol'] == '1') {
            $response->vrijednost = 'MUŠKARCI';
            $response->atribut_uid = '1';
        }

        return $response;
    }


    /**
     * @param array $data
     *
     * @return \stdClass
     */
    public static function checkSlimfitCategory(array $data): \stdClass
    {
        $response = new \stdClass();

        if ($data['jamstvo'] != '' && $data['jamstvo'] == 'slim') {
            $response->vrijednost = $data['jamstvo_naziv'];
            $response->atribut_uid = $data['jamstvo_uid	'];
        }

        return $response;
    }


    /**
     * @return \stdClass
     */
    public static function getDefault(): \stdClass
    {
        $response = new \stdClass();
        $response->vrijednost = '';
        $response->atribut_uid = '';

        return $response;
    }
}