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
     * @var array
     */
    private $list_excluded_names = [];

    /**
     * @var array
     */
    private $list_excluded_numbers = [];


    /**
     * LOC_Places constructor.
     *
     * @param null|object $places
     */
    public function __construct($places = null)
    {
        $this->loadExcluded();

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
        $second_target = ($target == 'cityname') ? 'zipcode' : 'cityname';
        if ($request != '') {
            $this->places = $this->places->sortBy($second_target)->filter(function ($item) use ($request, $target) {
                return stripos(strtolower($item[$target]), strtolower($request)) !== false;
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
        $list     = $this->loadXlsx('zip');
        $response = [];

        if ( ! empty($list)) {
            for ($i = 1; $i < count($list); $i++) {
                if ( ! in_array($list[$i][2], $this->list_excluded_names)/* && ! in_array($list[$i][1], $this->list_excluded_numbers)*/) {
                    $response[] = [
                        $list[0][0] => $list[$i][0],
                        $list[0][1] => $list[$i][1],
                        $list[0][2] => $list[$i][2],
                    ];
                }
            }

            return collect($response);
        }

        return [];
    }


    /**
     * $list[$i][...]
     * [0] => Broj Poštanskog ureda
     * [1] => Naziv poštanskogureda
     * [2] => Naselje
     *
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    private function loadExcluded()
    {
        $list = $this->loadXlsx('excluded');

        if ( ! empty($list)) {
            for ($i = 2; $i < count($list); $i++) {
                array_push($this->list_excluded_names, $list[$i][2]);
                //array_push($this->list_excluded_numbers, $list[$i][0]);
            }

            $this->list_excluded_names = collect($this->list_excluded_names)->unique()->flatten(2)->all();
            //$this->list_excluded_numbers = collect($this->list_excluded_numbers)->unique()->flatten(2)->all();
        }

        return [];
    }


    /**
     * @param string $name
     *
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    private function loadXlsx(string $name): array
    {
        $reader      = IOFactory::createReader("Xlsx");
        $spreadsheet = $reader->load(DIR_STORAGE . 'upload/assets/' . $name . '.xlsx');

        return $spreadsheet->getActiveSheet()->toArray();
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