<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Database;
use Agmedia\Helpers\Log;
use Agmedia\Models\Category\Category;
use Agmedia\Models\Category\CategoryDescription;
use Agmedia\Models\Category\CategoryPath;
use Agmedia\Models\Category\CategoryToLayout;
use Agmedia\Models\Category\CategoryToStore;
use Agmedia\Models\Manufacturer\Manufacturer;
use Agmedia\Models\Product\Product;
use Agmedia\Models\Product\ProductCategory;
use Agmedia\Models\SeoUrl;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Class LOC_Product
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class LOC_Action
{

    /**
     * @var Database
     */
    private $db;

    /**
     * @var array
     */
    private $actions;

    /**
     * @var array
     */
    private $actions_to_add = [];

    /**
     * @var array
     */
    private $prices_to_update = [];

    /**
     * @var array
     */
    private $count;

    /**
     * @var array
     */
    private $insert_query;

    /**
     * @var array
     */
    private $insert_query_category;


    /**
     * LOC_Product constructor.
     *
     * @param $products
     */
    public function __construct($actions)
    {
        $this->actions = $this->setActions($actions);
        $this->db      = new Database(DB_DATABASE);
    }


    /**
     * @return Collection
     */
    public function getActions(): Collection
    {
        return collect($this->actions)/*->where('partner', '==', null)*/;
    }


    /**
     * @return Collection
     */
    public function getActionsToAdd(): Collection
    {
        return collect($this->actions_to_add);
    }


    /**
     * @return $this
     */
    public function collectWebPrices()
    {
        $this->prices_to_update = collect();
        $action = $this->getActions()
                        ->where('status', '!=', '1')
                        ->where('naziv', '=', 'web_cijene')->first();

        $categories = collect();
        $manufacturers = collect();

        foreach ($action->stavke as $item) {
            if ($item->grupa_artikla && ! is_null($item->mpc_rabat)) {

                if ($categories->has($item->grupa_artikla)) {
                    if ($item->grupa_artikla > $categories[$item->grupa_artikla]) {
                        $categories->put($item->grupa_artikla, $item->mpc_rabat);
                    }
                } else {
                    $categories->put($item->grupa_artikla, $item->mpc_rabat);
                }
            }

            if ($item->robna_marka && ! is_null($item->mpc_rabat)) {
                if ($manufacturers->has($item->robna_marka)) {
                    if ($item->robna_marka > $manufacturers[$item->robna_marka]) {
                        $manufacturers->put($item->robna_marka, $item->mpc_rabat);
                    }
                } else {
                    $manufacturers->put($item->robna_marka, $item->mpc_rabat);
                }
            }
        }

        foreach ($categories as $sifra => $discount) {
            $category = Category::where('luceed_uid', $sifra)->with('products')->first();

            if ($category) {
                $ids = ProductCategory::where('category_id', $category->category_id)->pluck('product_id');
                $products = Product::whereIn('product_id', $ids)->get();

                foreach ($products as $product) {
                    $this->prices_to_update->put($product->model, $this->calculateDiscountPrice($product->price_2, $discount));
                }
            }
        }

        foreach ($manufacturers as $sifra => $discount) {
            $manufacturer = Manufacturer::where('luceed_uid', $sifra)->with('products')->first();

            if ($manufacturer) {
                foreach ($manufacturer->products as $product) {
                    $this->prices_to_update->put($product->model, $this->calculateDiscountPrice($product->price_2, $discount));
                }
            }
        }

        foreach ($action->stavke as $item) {
            if ($item->mpc) {
                $this->prices_to_update->put($item->artikl, $item->mpc);
            }
        }

        return $this;
    }


    /**
     * @return $this
     * @throws \Exception
     */
    public function collectActive()
    {
        $actions = $this->getActions()
                        ->where('status', '=', '2')
                        ->where('naziv', '!=', 'web_cijene');

        foreach ($actions as $key => $action) {
            if ( ! empty($action->stavke) && $this->isForWeb($action)) {
                if (( ! $action->start_date || (Carbon::createFromFormat('d.m.Y', $action->start_date) < Carbon::now())) &&
                    ( ! $action->end_date || (Carbon::createFromFormat('d.m.Y', $action->end_date) > Carbon::now()))
                ) {
                    array_push($this->actions_to_add, $action);
                }
            }
        }

        return $this;
    }


    /**
     * @param $action
     *
     * @return bool
     */
    private function isForWeb($action): bool
    {
        if (empty($action->poslovne_jedinice)) {
            return true;
        }

        foreach ($action->poslovne_jedinice as $item) {
            if ($item->pj == '10') {
                return true;
            }
        }

        return false;
    }


    /**
     * @return $this
     */
    public function sortActions()
    {
        $specials = collect();
        $this->insert_query = '';
        $this->insert_query_category = '';
        $this->count        = 0;
        $cat_action_id = agconf('import.default_action_category');

        $this->deleteActionsCategoriesDB();

        foreach ($this->getActionsToAdd() as $key => $action) {
            $data = [
                'naziv' => str_replace('web_', '', $action->naziv),
                'grupa_artikla' => $action->akcija_uid ?: '0'
            ];

            $loc = new LOC_Category();
            $category = $loc->save($data, $cat_action_id, $key);

            if ($category) {
                foreach ($action->stavke as $item) {
                    $item->category = $category;

                    $start_time = $this->checkTime($action->start_time);
                    $end_time = $this->checkTime($action->end_time);

                    $item->start = $action->start_date . ' ' . $start_time;
                    $item->end = $action->end_date . ' ' . $end_time;

                    $specials->push($item);
                }
            }
        }

        $temps = $specials->groupBy('artikl_uid')->all();

        foreach ($temps as $item) {
            $mpc = $item->first()->mpc;
            $product = Product::where('model', $item->first()->artikl)->first();

            if ( ! $mpc && $item->first()->mpc_rabat) {
                $mpc = $this->calculateDiscountPrice($product->price, $item->first()->mpc_rabat);
            }

            if ($product && $mpc) {
                $start = Carbon::createFromFormat('d.m.Y H:i:s', $item->first()->start)->format('Y-m-d H:i:s');
                $end   = Carbon::createFromFormat('d.m.Y H:i:s', $item->first()->end)->format('Y-m-d H:i:s');

                //$end = date('Y-m-d', strtotime("+1 day", strtotime($end)));

                $this->insert_query .= '(' . $product->product_id . ', 1, 0, ' . $mpc . ', "' . $start . '", "' . $end . '"),';
                $this->insert_query_category .= '(' . $product->product_id . ',' . $item->first()->category . '),';

                $this->count++;
            }
        }

        return $this;
    }




    /**
     * @return array|false
     * @throws \Exception
     */
    public function import()
    {
        $inserted = 0;
        $this->deleteActionsDB();

        try {
            $inserted = $this->db->query("INSERT INTO " . DB_PREFIX . "product_special (product_id, customer_group_id, priority, price, date_start, date_end) VALUES " . substr($this->insert_query, 0, -1) . ";");
        }
        catch (\Exception $exception) {
            Log::store($exception->getMessage(), 'import_actions_query');
        }


        if ($inserted) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category (product_id, category_id) VALUES " . substr($this->insert_query_category, 0, -1) . ";");

            return $this->count;
        }

        return false;
    }


    /**
     * @param string $type
     *
     * @return int
     * @throws \Exception
     */
    public function update(string $type = 'prices')
    {
        if ($type == 'prices') {
            if ( ! empty($this->prices_to_update) && $this->prices_to_update->count()) {
                $this->deleteProductTempDB();

                $temp_product = '';

                foreach ($this->prices_to_update as $sifra => $price) {
                    $temp_product .= '("' . $sifra . '", 0, ' . $price . '),';
                }

                $this->db->query("INSERT INTO " . DB_PREFIX . "product_temp (uid, quantity, price) VALUES " . substr($temp_product, 0, -1) . ";");
                $this->db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_temp pt ON p.model = pt.uid SET p.price = pt.price");

                return $this->prices_to_update->count();
            }
        }

        return 0;
    }


    /**
     * @param string $time
     *
     * @return string
     */
    private function checkTime(string $time): string
    {
        if (substr($time, 0, 1) == '0') {
            return '00:00:01';
        }

        return $time;
    }


    /**
     * Return the corrected response from luceed service.
     * Without unnecessary tags.
     *
     * @param $products
     *
     * @return array
     */
    private function setActions($actions): array
    {
        $prods = json_decode($actions);

        return $prods->result[0]->akcije;
    }


    /**
     * @throws \Exception
     */
    private function deleteActionsDB(): void
    {
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "product_special`");
    }


    /**
     *
     */
    private function deleteActionsCategoriesDB(): void
    {
        $categories = Category::where('parent_id', agconf('import.default_action_category'))->get();

        foreach ($categories as $category) {
            CategoryToStore::where('category_id', $category->category_id)->delete();
            CategoryToLayout::where('category_id', $category->category_id)->delete();
            CategoryPath::where('category_id', $category->category_id)->delete();
            CategoryDescription::where('category_id', $category->category_id)->delete();
            SeoUrl::where('query', 'category_id=' . $category->category_id)->delete();
        }

        Category::where('parent_id', agconf('import.default_action_category'))->delete();
    }


    /**
     * @throws \Exception
     */
    private function deleteProductTempDB(): void
    {
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "product_temp`");
    }


    /**
     * @param float $price
     * @param int   $discount
     *
     * @return float|int
     */
    private function calculateDiscountPrice(float $price, int $discount)
    {
        if ( ! $discount) {
            return $price;
        }

        return $price - ($price * ($discount / 100));
    }
}