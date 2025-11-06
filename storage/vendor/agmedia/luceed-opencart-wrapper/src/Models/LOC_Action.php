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
     * @var Collection
     */
    private $prices_to_update = [];

    /**
     * @var int
     */
    private $count = 0;

    /**
     * @var string
     */
    private $insert_query = '';

    /**
     * @var string
     */
    private $insert_query_category = '';

    /**
     * LOC_Product constructor.
     *
     * @param string $actions JSON string iz API-ja
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
            ->where('naziv', '=', 'web_cijene')
            ->first();

        if (!$action) {
            return $this;
        }

        $categories = collect();
        $manufacturers = collect();

        foreach ($action->stavke as $item) {
            // BUGFIX: uspoređujemo rabate međusobno, ne šifru s rabatom
            if (!empty($item->grupa_artikla) && $item->mpc_rabat !== null) {
                if ($categories->has($item->grupa_artikla)) {
                    if ((int) $item->mpc_rabat > (int) $categories[$item->grupa_artikla]) {
                        $categories->put($item->grupa_artikla, (int) $item->mpc_rabat);
                    }
                } else {
                    $categories->put($item->grupa_artikla, (int) $item->mpc_rabat);
                }
            }

            if (!empty($item->robna_marka) && $item->mpc_rabat !== null) {
                if ($manufacturers->has($item->robna_marka)) {
                    if ((int) $item->mpc_rabat > (int) $manufacturers[$item->robna_marka]) {
                        $manufacturers->put($item->robna_marka, (int) $item->mpc_rabat);
                    }
                } else {
                    $manufacturers->put($item->robna_marka, (int) $item->mpc_rabat);
                }
            }
        }

        foreach ($categories as $sifra => $discount) {
            $category = Category::where('luceed_uid', $sifra)->with('products')->first();

            if ($category) {
                $ids = ProductCategory::where('category_id', $category->category_id)->pluck('product_id');
                $products = Product::whereIn('product_id', $ids)->get();

                foreach ($products as $product) {
                    $price = $this->calculateDiscountPrice((float)$product->price_2, (int)$discount);
                    $this->prices_to_update->put($product->model, $price);
                }
            }
        }

        foreach ($manufacturers as $sifra => $discount) {
            $manufacturer = Manufacturer::where('luceed_uid', $sifra)->with('products')->first();

            if ($manufacturer) {
                foreach ($manufacturer->products as $product) {
                    $price = $this->calculateDiscountPrice((float)$product->price_2, (int)$discount);
                    $this->prices_to_update->put($product->model, $price);
                }
            }
        }

        foreach ($action->stavke as $item) {
            if (!empty($item->mpc)) {
                $this->prices_to_update->put($item->artikl, (float)$item->mpc);
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
        // Uzimamo sve osim status == '1'
        $this->actions_to_add = $this->getActions();

        return $this;
    }

    /**
     * Sortira i priprema VALUES za bulk INSERT u product_special.
     * – koristi stvarne datume iz API-ja (ako postoje)
     * – koristi NULL umjesto "0000-00-00"
     * – gradi values preko niza i implode (nema trailing comma)
     *
     * @return $this
     */
    public function sortActions()
    {
        $values = [];
        $this->insert_query = '';
        $this->insert_query_category = '';
        $this->count = 0;
        $cat_action_id = agconf('import.default_action_category');

        $this->deleteActionsCategoriesDB();

        foreach ($this->getActionsToAdd() as $actions) {
            // Parsiranje datuma iz API-ja "6.11.2025" → "2025-11-06"
            $sd = null;
            $ed = null;

            try {
                if (!empty($actions->start_date)) {
                    $sd = Carbon::createFromFormat('j.n.Y', trim($actions->start_date))->toDateString();
                }
                if (!empty($actions->end_date)) {
                    $ed = Carbon::createFromFormat('j.n.Y', trim($actions->end_date))->toDateString();
                }
            } catch (\Throwable $e) {
                $sd = $ed = null;
            }

            foreach ($actions->stavke as $action) {
                $artikl = $action->artikl ?? '';
                if ($artikl === '') {
                    continue;
                }

                // model je dio prije crtice; ako nema crtice, cijeli string
                $dashPos = strpos($artikl, '-');
                $model = $dashPos !== false ? substr($artikl, 0, $dashPos) : $artikl;

                if ($model === '' || $action->mpc_rabat === null) {
                    continue;
                }

                $product = Product::where('model', $model)->first();
                if (!$product) {
                    continue;
                }

                // cijena sa popustom
                $mpc = $this->calculateDiscountPrice((float)$product->price, (int)$action->mpc_rabat);
                $price = number_format($mpc, 4, '.', '');

                $dateStart = $sd ? '"' . $sd . '"' : 'NULL';
                $dateEnd   = $ed ? '"' . $ed . '"' : 'NULL';

                $values[] = '(' . (int)$product->product_id . ', 1, 0, ' . $price . ', ' . $dateStart . ', ' . $dateEnd . ')';
            }
        }

        if (!empty($values)) {
            $this->insert_query = implode(',', $values);
            $this->count = count($values);
        }

        return $this;
    }

    /**
     * @return int|false
     * @throws \Exception
     */
    public function import()
    {
        $inserted = 0;

        // TRUNCATE staro — ostavljam kako već imaš
        $this->deleteActionsDB();

        // GUARD: nema values → nema inserta (izbjegava "VALUES ;")
        if (empty($this->insert_query)) {
            return 0;
        }

        try {
            $sql = "INSERT INTO " . DB_PREFIX . "product_special (product_id, customer_group_id, priority, price, date_start, date_end) VALUES " . $this->insert_query . ";";
            $inserted = $this->db->query($sql);
        } catch (\Exception $exception) {
            Log::store($exception->getMessage(), 'import_actions_query');
            return false;
        }

        if ($inserted) {
            if ($this->insert_query_category != '') {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category (product_id, category_id) VALUES " . substr($this->insert_query_category, 0, -1) . ";");
            }

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
            if (!empty($this->prices_to_update) && $this->prices_to_update->count()) {
                $this->deleteProductTempDB();

                $temp_product = '';
                foreach ($this->prices_to_update as $sifra => $price) {
                    $price = number_format((float)$price, 4, '.', '');
                    $temp_product .= '("' . $sifra . '", 0, ' . $price . '),';
                }

                // već imamo guard count() iznad; ovdje dodatno provjeri radi sigurnosti
                if ($temp_product !== '') {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_temp (uid, quantity, price) VALUES " . substr($temp_product, 0, -1) . ";");
                    $this->db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_temp pt ON p.model = pt.uid SET p.price = pt.price");
                }

                return $this->prices_to_update->count();
            }
        }

        return 0;
    }

    /**
     * @return int|bool|\stdClass
     * @throws \Exception
     */
    public function updateSpecialsFromTemp()
    {
        $temp = $this->db->query('SELECT LEFT(sku, 6) as sku, price, special FROM temp GROUP BY LEFT(sku, 6);');

        $values = [];
        foreach ($temp->rows as $row) {
            $sku = $row['sku'] ?? null;
            $special = $row['special'] ?? null;

            if (!$sku || $special === null || $special === '') {
                continue;
            }

            $product = Product::query()->where('model', $sku)->first();
            if (!$product) {
                continue;
            }

            $specialNum = number_format((float)$special, 4, '.', '');
            // Datumi kao NULL (izbjegni "0000-00-00" i NO_ZERO_DATE)
            $values[] = '(' . (int)$product->product_id . ', 1, 0, ' . $specialNum . ', NULL, NULL)';
        }

        if (empty($values)) {
            return 0; // nema inserta → nema 1064
        }

        $query_p = "INSERT INTO " . DB_PREFIX . "product_special (product_id, customer_group_id, priority, price, date_start, date_end) VALUES " . implode(',', $values) . ";";

        return $this->db->query($query_p);
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
     * @param string $actions
     *
     * @return array
     */
    private function setActions($actions): array
    {
        // ROBUST: provjera JSON-a i strukture
        $prods = json_decode($actions);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }
        if (!isset($prods->result[0]->akcije) || !is_array($prods->result[0]->akcije)) {
            return [];
        }

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
     * @return float
     */
    private function calculateDiscountPrice(float $price, int $discount)
    {
        if (!$discount) {
            return $price;
        }

        return $price - ($price * ($discount / 100));
    }
}
