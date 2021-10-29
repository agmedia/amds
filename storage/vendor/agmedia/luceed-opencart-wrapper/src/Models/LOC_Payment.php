<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Log;
use Illuminate\Support\Collection;

/**
 * Class LOC_Category
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class LOC_Payment
{

    /**
     * @var array
     */
    public $payments;

    /**
     * @var array
     */
    private $list = [];


    /**
     * LOC_Places constructor.
     *
     * @param null $payments
     */
    public function __construct($payments = null)
    {
        if ($payments) {
            $this->list = $this->setPayments($payments);
        } else {
            $this->list = $this->load();
        }
    }


    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getList(string $name = ''): Collection
    {
        $this->payments = collect($this->list)->whereIn('naziv', agconf('import.payments.included'));

        if ($name != '') {
            return $this->payments->where('naziv', $name);
        }

        return $this->payments;
    }


    /**
     * @return int
     */
    public function import(Collection $list = null)
    {
        $imported = 0;

        if ($list) {
            $imported = file_put_contents(agconf('import.payments.json'), $list->toJson());
        }

        return $imported;
    }


    /**
     * @return array|Collection
     */
    public function load()
    {
        $file = json_decode(file_get_contents(agconf('import.payments.json')),TRUE);

        if ($file) {
            return collect($file);
        }

        return [];
    }


    /**
     * @param $payments
     *
     * @return array
     */
    private function setPayments($payments): array
    {
        $cats = json_decode($payments);

        return $cats->result[0]->vrsta_placanja;
    }
}