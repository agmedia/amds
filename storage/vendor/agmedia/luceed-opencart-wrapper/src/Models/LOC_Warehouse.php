<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Log;
use Agmedia\Luceed\Facade\LuceedProduct;
use Agmedia\Models\Location;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Class LOC_Category
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class LOC_Warehouse
{

    /**
     * @var array
     */
    private $list = [];

    /**
     * @var array
     */
    private $warehouses;


    /**
     * LOC_Category constructor.
     *
     * @param null $warehouses
     */
    public function __construct($warehouses = null)
    {
        if ($warehouses) {
            $this->list = $this->setWarehouses($warehouses);
        } else {
            $this->list = $this->load();
        }
    }


    /**
     * @return Collection
     */
    public function getList(): Collection
    {
        return collect($this->list)->where('skladiste', '!=', '')
                                   ->where('naziv', '!=', '');
    }


    /**
     * @return Collection
     */
    public function getWarehouses(): Collection
    {
        $incl = agconf('import.warehouse.included');

        if ( ! empty($incl)) {
            return $this->getList()
                        ->whereIn('skladiste', $incl);
        }

        return $this->getList();
    }


    /**
     * @return Collection
     */
    public function getDefaultWarehouses(): Collection
    {
        return $this->getList()
                    ->whereIn('skladiste', agconf('import.warehouse.default'));
    }


    /**
     * @return Collection
     */
    public function getAvailabilityViewWarehouses(): Collection
    {
        return $this->getList()
                    ->whereIn('skladiste', agconf('import.warehouse.availability_view'));
    }


    /**
     * @param $product
     *
     * @return Collection
     */
    public function getAvailabilityForProductTemp(string $product): Collection
    {
        $response = collect();
        $houses = $this->getAvailabilityViewWarehouses();
        $units = $this->getUnitsQuery($houses);
        $houses_default = $this->getDefaultWarehouses();
        $units_default = $this->getUnitsQuery($houses_default);
        $houses_stores = $this->getList()->whereIn('skladiste', agconf('import.warehouse.stores'));
        $units_stores = $this->getUnitsQuery($houses_stores);

        $availables = collect($this->setAvailables(
            LuceedProduct::stock($units, $product)
        ));

        $defaults = collect($this->setAvailables(
            LuceedProduct::stock($units_default, $product)
        ));

        $stores = collect($this->setAvailables(
            LuceedProduct::stock($units_stores, $product)
        ));

        $suplier = collect($this->setSuplierStock(
            LuceedProduct::getSuplierStock($product)
        ))->where('main', 'D')->first();

        // AVAILABILITY VIEW
        foreach ($houses as $house) {
            $has = $availables->where('skladiste_uid', $house['skladiste_uid'])->first();

            if ($has) {
                $qty = $has->raspolozivo_kol;

                if ($qty < 0) {
                    $qty = 0;
                }

                $response->push([
                    'title' => $house['naziv'],
                    'address' => $house['adresa'],
                    'qty'   => $qty
                ]);

            } else {
                $response->push([
                    'title' => $house['naziv'],
                    'address' => $house['adresa'],
                    'qty'   => 0
                ]);
            }
        }

        $qty_default = 0;
        // DEFAULT WAREHOUSE COUNT
        foreach ($houses_default as $house) {
            $has = $defaults->where('skladiste_uid', $house['skladiste_uid'])->first();

            if ($has) {
                $qty_default += max($has->raspolozivo_kol, 0);
            }
        }

        /*if ($qty_default < 0) {
            $qty_default = 0;
        }*/

        $qty_stores = 0;
        // STORES WAREHOUSE COUNT
        foreach ($houses_stores as $house) {
            $has = $stores->where('skladiste_uid', $house['skladiste_uid'])->first();

            if ($has) {
                $qty_stores += $has->raspolozivo_kol;
            }
        }

        if ($qty_stores < 0) {
            $qty_stores = 0;
        }

        $title = '';
        $btn = '';
        $button = '';
        $date = '';

        if ($qty_default) {
            $title = 'success';
            $date = Carbon::now()->addWeekdays(1);
            $btn = 'DOSTUPNO ODMAH';
            $date = ($date->diff(Carbon::now())->days < 1) ? 'Šaljemo sutra' : 'Šaljemo do ' . $date->format('d.m.Y');;
        }

        if ( ! $qty_default && ! $suplier->dobavljac_stanje && $qty_stores) {
            $title = 'secondary';
            $btn = 'NEDOSTUPNO NA WEBU';
            $button = 'Nedostupno online';
            $date = 0;
        }

        if ( ! $qty_default && $suplier->dobavljac_stanje) {
            $title = 'warning';
            $btn = 'DOSTUPNO NA IZDVOJENOM SKLADIŠTU';
            $button = 'Stavi u košaricu';
            $date = 'Šaljemo do ' . Carbon::now()->addWeekdays(5)->format('d.m.Y');
        }

        if ( ! $qty_default && ! $suplier->dobavljac_stanje && ! $qty_stores) {
            $title = 'secondary';
            $btn = 'PROIZVOD NEDOSTUPAN';
            $button = 'Nedostupno';
            $date = 0;
        }

        $response->push([
            'title' => 'Web',
            'address' => '',
            'qty'   => $qty_default
        ]);

        $response->push([
            'title' => 'Dobavljač',
            'address' => '',
            'qty'   => $suplier->dobavljac_stanje
        ]);

        $response->push([
            'title' => 'Btn',
            'btn' => $title,
            'button' => $button,
            'address' => $btn,
            'qty'   => $date
        ]);

        return $response;
    }


    public function getAvailabilityForProduct(string $product): Collection
    {
        $response = collect();
        $locations = Location::all();
        $units = $locations->pluck('skladiste')->filter()->unique()->values();
        $locations_by_uid = $locations->keyBy('skladiste_uid');
        $locations_by_store = $locations->filter(function ($location) {
            return ! empty($location['skladiste']);
        })->groupBy('skladiste');
        $warehouses = $this->getList()->keyBy('skladiste_uid');
        $used_keys = [];

        $availables = collect($this->setAvailables(
            LuceedProduct::stock($this->getUnitsQuery($units), urlencode($product))
        ));

        if ($availables->isEmpty()) {
            return collect([
                'error' => 'Trenutno nema raspoloživih količina!'
            ]);
        }

        foreach ($availables as $available) {
            $qty = (float) $available->raspolozivo_kol;

            if ($qty <= 0 || empty($available->skladiste_uid)) {
                continue;
            }

            $resolved = $this->resolveAvailabilityLocation(
                $available->skladiste_uid,
                $locations_by_uid,
                $locations_by_store,
                $warehouses
            );

            if ( ! $resolved) {
                continue;
            }

            if (isset($used_keys[$resolved['key']])) {
                continue;
            }

            $used_keys[$resolved['key']] = true;
            $response->push($resolved['data']);
        }

        return $response;
    }


    /**
     * @param string     $warehouse_uid
     * @param Collection $locations_by_uid
     * @param Collection $locations_by_store
     * @param Collection $warehouses
     *
     * @return array|null
     */
    private function resolveAvailabilityLocation(
        string $warehouse_uid,
        Collection $locations_by_uid,
        Collection $locations_by_store,
        Collection $warehouses
    ): ?array {
        $location = $locations_by_uid->get($warehouse_uid);

        if ($location) {
            if ($this->isVisibleLocation($location)) {
                return [
                    'key'  => 'location:' . $location['location_id'],
                    'data' => $this->mapLocationData($location)
                ];
            }

            // If the location exists locally but is hidden, keep it hidden.
            return null;
        }

        $warehouse = $warehouses->get($warehouse_uid);
        $store_code = $this->getWarehouseStoreCode($warehouse);

        if ($store_code && $locations_by_store->has($store_code)) {
            foreach ($locations_by_store->get($store_code) as $store_location) {
                if ($this->isVisibleLocation($store_location)) {
                    return [
                        'key'  => 'location:' . $store_location['location_id'],
                        'data' => $this->mapLocationData($store_location)
                    ];
                }
            }
        }

        if ($warehouse && $this->isDisplayWarehouse($warehouse)) {
            return [
                'key'  => 'warehouse:' . $warehouse_uid,
                'data' => $this->mapWarehouseData($warehouse)
            ];
        }

        return null;
    }


    /**
     * @param array|\ArrayAccess|null $location
     *
     * @return bool
     */
    private function isVisibleLocation($location): bool
    {
        return $location
            && (int) $location['vidljivost'] === 1
            && ! empty($location['skladiste'])
            && ! empty($location['skladiste_uid']);
    }


    /**
     * @param array $location
     *
     * @return array
     */
    private function mapLocationData($location): array
    {
        return [
            'name'      => $location['name'],
            'uid'       => $location['skladiste_uid'],
            'geocode'   => $location['geocode'],
            'address'   => $location['address'],
            'telephone' => $location['telephone'],
            'email'     => $location['fax'],
            'open'      => $location['open'],
        ];
    }


    /**
     * @param array $warehouse
     *
     * @return array
     */
    private function mapWarehouseData(array $warehouse): array
    {
        return [
            'name'      => $warehouse['pj_naziv'] ?: $warehouse['naziv'],
            'uid'       => $warehouse['skladiste_uid'],
            'geocode'   => '',
            'address'   => $this->getWarehouseAddress($warehouse),
            'telephone' => $warehouse['telefon'] ?: '',
            'email'     => $warehouse['e_mail'] ?: '',
            'open'      => '',
        ];
    }


    /**
     * @param array|null $warehouse
     *
     * @return string
     */
    private function getWarehouseStoreCode(?array $warehouse = null): string
    {
        if ( ! $warehouse) {
            return '';
        }

        if ( ! empty($warehouse['pj'])) {
            return trim($warehouse['pj']);
        }

        if ( ! empty($warehouse['skladiste'])) {
            return trim($warehouse['skladiste']);
        }

        return '';
    }


    /**
     * @param array $warehouse
     *
     * @return bool
     */
    private function isDisplayWarehouse(array $warehouse): bool
    {
        return (bool) preg_match('/^(D|K|P)[0-9A-Z]+$/', $this->getWarehouseStoreCode($warehouse));
    }


    /**
     * @param array $warehouse
     *
     * @return string
     */
    private function getWarehouseAddress(array $warehouse): string
    {
        $parts = [];

        foreach (['adresa', 'postanski_broj', 'mjesto'] as $field) {
            if ( ! empty($warehouse[$field])) {
                $parts[] = trim($warehouse[$field]);
            }
        }

        return implode(', ', array_unique($parts));
    }


    /**
     * @return int
     */
    public function import(?Collection $list = null)
    {
        $imported = 0;

        if ($list) {
            $imported = file_put_contents(agconf('import.warehouse.json'), $list->toJson());
        }

        return $imported;
    }


    /**
     * @return array|Collection
     */
    public function load()
    {
        $file = json_decode(file_get_contents(agconf('import.warehouse.json')),TRUE);

        if ($file) {
            return collect($file);
        }

        return [];
    }


    /**
     * @param $units
     *
     * @return string
     */
    private function getUnitsQuery($units)
    {
        $string = '[';

        foreach ($units as $unit) {
            $string .= $unit . ',';
        }

        $string = substr($string, 0, -1);

        $string .= ']';

        return $string;
    }


    /**
     * @param $warehouses
     *
     * @return array
     */
    private function setWarehouses($warehouses): array
    {
        $json = json_decode($warehouses);

        return $json->result[0]->skladista;
    }

    /**
     * @param $warehouses
     *
     * @return array
     */
    private function setAvailables($items): array
    {
        $json = json_decode($items);

        if (isset($json->result) && is_array($json->result)) {
            $response = [];

            foreach ($json->result as $result) {
                if (isset($result->stanje) && is_array($result->stanje)) {
                    $response = array_merge($response, $result->stanje);
                }
            }

            return $response;
        }

        return [];
    }


    /**
     * @param $stock
     *
     * @return mixed
     */
    private function setSuplierStock($stock)
    {
        $json = json_decode($stock);

        return $json->result[0]->artikli_dobavljaci;
    }
}
