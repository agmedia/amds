<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Log;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Class LOC_Category
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class LOC_Places
{

    /**
     * @var array
     */
    public $places;

    /**
     * @var array
     */
    private $list = [];


    /**
     * LOC_Places constructor.
     *
     * @param null|object $places
     */
    public function __construct($places = null)
    {
        if ($places) {
            $this->list = $this->setPlaces($places);
        } else {
            $this->list = $this->load();
        }
    }


    /**
     * @param string $state
     *
     * @return mixed
     */
    public function getList(string $state = 'HR')
    {
        $this->places = collect($this->list)->where('ctrcode', '==', $state);

        return $this;
    }


    /**
     * @param string $zip
     * @param string $city
     *
     * @return \stdClass|false
     */
    public function resolveUID(string $zip, string $city)
    {
        foreach (collect($this->list)->all() as $item) {
            if (strcasecmp($item->naziv, $city) == 0 && strcasecmp($item->postanski_broj, $zip) == 0) {
                return $item;
            }
        }

        return false;
    }


    /**
     * @param string $request
     * @param string $target = cityname | zipcode
     *
     * @return Collection
     */
    public function find(string $request = '', string $target = 'cityname')
    {
        if ($request != '') {
            $this->places = $this->places->filter(function ($item) use ($request, $target) {
                return stripos($item[$target], $request) !== false;
            });
        }

        return $this;
    }


    /**
     * @param int $count
     *
     * @return $this
     */
    public function limit(int $count = 0)
    {
        if ($count) {
            $this->places = $this->places->take($count);
        }

        return $this;
    }


    /**
     * @return array|Collection
     */
    public function load()
    {
        $reader      = IOFactory::createReader("Xlsx");
        $spreadsheet = $reader->load(DIR_STORAGE . 'upload/assets/zip.xlsx');
        $list        = $spreadsheet->getActiveSheet()->toArray();
        $response    = [];

        if ( ! empty($list)) {
            for ($i = 0; $i < count($list); $i++) {
                $response[] = [
                    $list[0][0] => $list[$i][0],
                    $list[0][1] => $list[$i][1],
                    $list[0][2] => $list[$i][2],
                ];
            }

            unset($response[0]);

            return collect($response);
        }

        return [];
    }


    /**
     * @param $places
     *
     * @return array
     */
    private function setPlaces($places): array
    {
        $cats = json_decode($places);

        if (isset($cats->result[0]->mjesta)) {
            return $cats->result[0]->mjesta;
        }

        return [];
    }
}