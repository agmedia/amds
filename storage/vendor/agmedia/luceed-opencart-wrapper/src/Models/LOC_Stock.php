<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Database;
use Agmedia\Helpers\Log;
use Agmedia\Models\Product\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Class LOC_Product
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class LOC_Stock
{

    /**
     * @var Database
     */
    private $db;

    /**
     * @var
     */
    public $dobavljaci = null;

    /**
     * @var
     */
    public $skladista = null;

    /**
     * @var null
     */
    private $skladista_stock = null;

    /**
     * @var null
     */
    private $dobavljaci_stock = null;

    /**
     * @var null
     */
    private $skladista_sorted = null;

    private $dobavljaci_sorted = null;

    private $skladista_query = '';

    private $dobavljaci_query = '';

    private $status;

    /**
     * LOC_Product constructor.
     */
    public function __construct()
    {
        $this->db = new Database(DB_DATABASE);
        $this->status = agconf('import.default_stock_empty');
    }


    /**
     * @return $this
     */
    public function sort()
    {
        // SKLADIŠTA
        if ($this->skladista && ! $this->skladista_sorted) {
            if ( ! $this->skladista_stock) {
                $this->skladista_stock = collect();
            }

            foreach ($this->skladista->groupBy('artikl_uid')->all() as $key => $item) {
                //$qty = $item->sum('raspolozivo_kol');

                $qty = 0;
                foreach ($item as $stock) {
                    $qty = $qty + max($stock->raspolozivo_kol, 0);
                }

                if ($qty) {
                    $this->status = agconf('import.default_stock_full');
                } else {
                    $this->status = agconf('import.default_stock_empty');
                }

                $this->skladista_stock->push([
                    'artikl_uid' => $key,
                    'stanje_kol' => $qty,
                    'stock_status' => $this->status
                ]);
            }

            $this->skladista_sorted = true;
        }

        // DOBAVLJAČI
        if ($this->dobavljaci && ! $this->dobavljaci_sorted) {
            if ( ! $this->dobavljaci_stock) {
                $this->dobavljaci_stock = collect();
            }

            foreach ($this->dobavljaci->where('main', 'D')->groupBy('sifra_artikla')->all() as $key => $item) {
                //$qty = $item->sum('dobavljac_stanje');

                $qty = 0;
                foreach ($item as $stock) {
                    $qty = $qty + max($stock->dobavljac_stanje, 0);
                }

                if ($this->status == agconf('import.default_stock_empty') && $qty) {
                    $this->status = agconf('import.default_stock_full');
                }

                $this->dobavljaci_stock->push([
                    'artikl' => $key,
                    'stanje_kol' => $qty,
                    'stock_status' => $this->status
                ]);
            }

            $this->dobavljaci_sorted = true;
        }

        return $this;
    }


    /**
     * @return $this
     */
    public function createQuery()
    {
        if ($this->skladista_sorted && $this->skladista_stock) {
            foreach ($this->skladista_stock as $item) {
                $this->skladista_query .= '("' . $item['artikl_uid'] . '", ' . $item['stanje_kol'] . ', ' . $item['stock_status'] . '),';
            }
        }

        if ($this->dobavljaci_sorted && $this->dobavljaci_stock) {
            foreach ($this->dobavljaci_stock as $item) {
                $this->dobavljaci_query .= '("' . $item['artikl'] . '", ' . $item['stanje_kol'] . ', ' . $item['stock_status'] . '),';
            }
        }

        return $this;
    }


    /**
     * @return int
     */
    public function update(): int
    {
        $this->truncateProductsQuantity();

        if ($this->skladista_query != '') {
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_temp (uid, quantity, price) VALUES " . substr($this->skladista_query, 0, -1) . ";");
            $this->db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_temp pt ON p.luceed_uid = pt.uid SET p.quantity = pt.quantity, p.stock_status_id = pt.price");

            $this->deleteProductTempDB();

            if ($this->dobavljaci_query != '') {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_temp (uid, quantity, price) VALUES " . substr($this->dobavljaci_query, 0, -1) . ";");
                $this->db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_temp pt ON p.model = pt.uid SET p.quantity = p.quantity + pt.quantity, p.stock_status_id = pt.price");
            }

            return 1;
        }

        return 0;
    }


    /**
     * Return the corrected response from luceed service.
     * Without unnecessary tags.
     *
     * @param $dobavljaci
     *
     * @return $this
     */
    public function setDobavljaci($dobavljaci)
    {
        $json = json_decode($dobavljaci);

        $this->dobavljaci = collect($json->result[0]->artikli_dobavljaci);

        return $this;
    }


    /**
     * Return the corrected response from luceed service.
     * Without unnecessary tags.
     *
     * @param $dobavljaci
     *
     * @return $this
     */
    public function setSkladista($skladista)
    {
        $json = json_decode($skladista);

        $this->skladista = collect($json->result[0]->stanje);

        return $this;
    }


    /**
     * @throws \Exception
     */
    private function deleteProductTempDB(): void
    {
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "product_temp`");
    }


    /**
     * @throws \Exception
     */
    private function truncateProductsQuantity(): void
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "product` SET quantity = 0 WHERE 1");
    }

}