<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Database;
use Agmedia\Helpers\Log;
use Agmedia\Kaonekad\AttributeHelper;
use Agmedia\Kaonekad\ScaleHelper;
use Agmedia\Luceed\Facade\LuceedProduct;
use Agmedia\Luceed\Models\LuceedProductForRevision;
use Agmedia\LuceedOpencartWrapper\Helpers\ProductHelper;
use Agmedia\Models\Attribute\Attribute;
use Agmedia\Models\Attribute\AttributeDescription;
use Agmedia\Models\Category\Category;
use Agmedia\Models\Manufacturer\Manufacturer;
use Agmedia\Models\Option\OptionValueDescription;
use Agmedia\Models\Product\Product;
use Agmedia\Models\Product\ProductDescription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Class LOC_Product
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class LOC_Product
{

    /**
     * @var array
     */
    private $products;

    /**
     * @var array
     */
    private $product;

    /**
     * @var array
     */
    private $existing;

    /**
     * @var array
     */
    private $products_to_add = [];

    /**
     * @var array
     */
    private $products_to_update = [];

    /**
     * @var int
     */
    private $default_category;

    /**
     * @var int
     */
    private $default_language;

    /**
     * @var string
     */
    private $image_path;


    /**
     * LOC_Product constructor.
     *
     * @param $products
     */
    public function __construct($products = null)
    {
        if ($products) {
            $this->products = $this->setProducts($products);
        }

        $this->default_category = agconf('import.default_category');
        $this->default_language = agconf('import.default_language');
        $this->image_path       = agconf('import.image_path');
    }


    /**
     * @return Collection
     */
    public function getProducts(): Collection
    {
        return collect($this->products);
    }


    /**
     * @return Collection
     */
    public function getProductsToAdd(): Collection
    {
        return collect($this->products_to_add);
    }


    /**
     * Check the difference between new,
     * and already imported products.
     *
     * @return $this
     */
    public function checkDiff()
    {
        // List of existing product identifiers.
        $this->existing = Product::pluck('model');
        // List of product identifiers without
        // existing products.
        $list_diff = $this->getProducts()
                          ->where('artikl', '!=', '')
                          ->where('naziv', '!=', '')
                          ->where('enabled', '!=', 'N')
                          ->where('webshop', '!=', 'N')
                          ->where('osnovni__artikl', '==', null)
                          ->pluck('artikl')
                          ->diff($this->existing)
                          ->flatten();

        // Full list of products to add to DB.
        $products_to_add = $this->getProducts()->whereIn('artikl', $list_diff)->values();
        $full_list       = $this->getProducts()
                                ->where('artikl', '!=', '')
                                ->where('naziv', '!=', '')
                                ->where('enabled', '!=', 'N')
                                ->where('webshop', '!=', 'N');

        $response = [];

        for ($i = 0; $i < $products_to_add->count(); $i++) {
            $product_options             = $full_list->where('osnovni__artikl', '==', $products_to_add[$i]->artikl)->all();
            $products_to_add[$i]->opcije = ProductHelper::sortOptions($product_options);

            $response[$products_to_add[$i]->artikl] = $products_to_add[$i];
        }

        $this->products_to_add = $response;

        return $this;
    }


    /**
     * Get some data only from products
     * that are in local Database.
     *
     * @return $this
     */
    public function sortForUpdate(string $products = null)
    {
        $start = microtime(true);
        // List of existing product identifiers.

        $this->existing = Product::query()->pluck('model')->take(300)->toArray();

        $full_list = $this->getProducts()
                          ->where('artikl', '!=', '')
                          ->where('naziv', '!=', '')
                          ->where('enabled', '!=', 'N')
                          ->where('webshop', '!=', 'N');

        //$this->existing = Product::query()->pluck('model')->diff($full_list->pluck('artikl'))->toArray();

        //$list = $full_list->where('osnovni__artikl', '==', null);

        Log::info(count($this->existing));

        $response = [];

        $product_options = $full_list->where('osnovni__artikl', '!=', null)->groupBy('osnovni__artikl')->all();

        for ($i = 0; $i < count($this->existing); $i++) {
            $main            = $full_list->where('artikl', '==', $this->existing[$i])->first();

            //Log::info($product_options[$this->existing[$i]]->toArray());

            if (isset($main->artikl)) {
                $response[$this->existing[$i]]         = $main;
                $response[$this->existing[$i]]->opcije = [];

                if ($product_options[$this->existing[$i]]) {
                    $response[$this->existing[$i]]->opcije = ProductHelper::sortOptions($product_options[$this->existing[$i]]->toArray());
                }

                /*Product::query()->where('model', $this->existing[$i])->update([
                    'status' => 1
                ]);*/

            } else {
                /*Product::query()->where('model', $this->existing[$i])->update([
                    'status' => 0
                ]);*/
            }
        }

        $end  = microtime(true);
        $time = number_format(($end - $start), 2, ',', '.');
        Log::store('SortForUpdate time ::: ' . $time . ' sec.', 'testing_update_time');

        // Full list of products to update.
        $this->products_to_add = $response;

        return $this;
    }


    /**
     * @param string $type
     *
     * @return false
     * @throws \Exception
     */
    public function update(string $type = 'all')
    {
        $db = new Database(DB_DATABASE);
        // Sort the temporary products DB import string.
        // (uid, price, quantity, stock_id)
        $query_str = '';

        foreach ($this->products_to_add as $item) {
            if (isset($item->mpc)) {
                $qty_sum = 0;

                if ( ! empty($item->opcije)) {
                    foreach ($item->opcije as $option) {
                        $qty_sum += $option['raspolozivo_kol'];
                    }
                } else {
                    $qty_sum += $item->raspolozivo_kol;
                }

                $stock = $qty_sum ?: 0;

                if ($stock) {
                    $stock_status_id = $stock ? agconf('import.default_stock_full') : agconf('import.default_stock_empty');
                    $query_str       .= '("' . $item->artikl . '", ' . $item->mpc . ', ' . $stock . ', ' . $stock_status_id . ', ' . (($stock > 0) ? 1 : 0) . '),';
                }

            }
        }
        
        Log::store($query_str, 'query_string');

        if ($query_str != '') {
            $db->query("INSERT INTO " . DB_PREFIX . "product_temp (uid, price, quantity, stock_id, status) VALUES " . substr($query_str, 0, -1) . ";");
            // Check wich type of update to conduct.
            // Price and quantity or each individualy?
            if ($type == 'all') {
                $db->query("UPDATE " . DB_PREFIX . "product p SET p.quantity = 0, p.status = 0");
                $updated = $db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_temp pt ON p.model = pt.uid SET p.quantity = pt.quantity, p.price = pt.price, p.stock_status_id = pt.stock_id, p.status = pt.status");
            }
            if ($type == 'price' || $type == 'prices') {
                $updated = $db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_temp pt ON p.model = pt.uid SET p.price = pt.price, p.status = pt.status");
            }
            if ($type == 'quantity' || $type == 'quantities') {
                $db->query("UPDATE " . DB_PREFIX . "product p SET p.quantity = 0, p.status = 0");
                $updated = $db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_temp pt ON p.model = pt.uid SET p.quantity = pt.quantity, p.stock_status_id = pt.stock_id, p.status = pt.status");
            }
        }

        $start = microtime(true);
        
        // Truncate the product_temp table.
        $db->query("TRUNCATE TABLE `" . DB_PREFIX . "product_temp`");

        $this->updateOptions($type);
        
        $end = microtime(true);
        $time = number_format(($end - $start), 2, ',', '.');
        Log::store('Update - Options time ::: ' . $time . ' sec.', 'testing_update_time');

        // Return products count if updated.
        // False if update error occurs.
        if ($updated) {
            return count($this->products_to_add);
        }

        return false;
    }


    /**
     * @param string $type
     *
     * @return bool
     * @throws \Exception
     */
    private function updateOptions(string $type = '')
    {
        $db = new Database(DB_DATABASE);
        $updated = false;
        $query_str = '';

        foreach ($this->products_to_add as $item) {
            if ( ! empty($item->opcije)) {
                foreach ($item->opcije as $option) {
                    $stock     = $option['raspolozivo_kol'] ?: 0;

                    if ($stock) {
                        $query_str .= '("' . $option['uid'] . '", 0, ' . $stock . ', 0),';
                    }
                }
            }
        }

        if ($query_str != '') {
            $db->query("INSERT INTO " . DB_PREFIX . "product_temp (uid, price, quantity, stock_id) VALUES " . substr($query_str, 0, -1) . ";");

            $db->query("UPDATE " . DB_PREFIX . "product_option_value p SET p.quantity = 0");
            $updated = $db->query("UPDATE " . DB_PREFIX . "product_option_value p INNER JOIN " . DB_PREFIX . "product_temp pt ON p.sku = pt.uid SET p.quantity = pt.quantity");

            $db->query("TRUNCATE TABLE `" . DB_PREFIX . "product_temp`");
        }

        return $updated ? true : false;
    }


    /**
     * @return int
     * @throws \Exception
     */
    public function populateLuceedData()
    {
        $count = 0;
        $db    = new Database(DB_DATABASE);

        $luceed_products = $this->getProducts()
                                ->where('artikl', '!=', '')
                                ->where('naziv', '!=', '')
                                ->where('webshop', '!=', 'N')
                                ->all();

        $query_str = '';

        foreach ($luceed_products as $product) {
            $product_array = ProductHelper::collectLuceedData($product);
            $hash          = ProductHelper::hashLuceedData($product_array);
            //$data = collect($product_array)->toJson();

            $query_str .= '("' . $product->artikl_uid . '", "' . $product->artikl . '", "' . base64_encode(serialize($product_array)) . '", "' . $hash . '"),';

            $count++;
        }

        $db->query("TRUNCATE TABLE " . DB_PREFIX . "product_luceed");
        $db->query("INSERT INTO " . DB_PREFIX . "product_luceed (uid, sifra, `data`, `hash`) VALUES " . substr($query_str, 0, -1) . ";");

        $db->query("TRUNCATE TABLE " . DB_PREFIX . "product_luceed_for_update");
        $res = $db->query("SELECT p.luceed_uid FROM oc_product p JOIN oc_product_luceed pl ON p.luceed_uid = pl.uid WHERE p.hash <> pl.hash;");

        if ($res->num_rows) {
            $query_str = '';
            foreach ($res->rows as $row) {
                $query_str .= '("' . $row['luceed_uid'] . '"),';
            }

            $db->query("INSERT INTO " . DB_PREFIX . "product_luceed_for_update (uid) VALUES " . substr($query_str, 0, -1) . ";");
        }

        $products_count = Product::pluck('sku')->count();

        return [
            'status'    => 200,
            'total'     => $count,
            'inserting' => max($count - $products_count, 0),
            'updating'  => $res->num_rows,//floor($count - ($count - ($diff->num_rows / 2)))
        ];
    }


    /**
     * @return $this
     */
    public function cleanRevisionTable($uids = null)
    {
        $exist = Product::pluck('sku');
        $revs  = LuceedProductForRevision::pluck('sku');
        LuceedProductForRevision::whereIn('sku', $revs->diff($exist))->delete();

        if ($uids) {
            LuceedProductForRevision::whereIn('uid', $uids)->delete();
        } else {
            LuceedProductForRevision::truncate();
        }

        return $this;
    }


    /**
     * @return mixed
     */
    public function checkRevisionTable()
    {
        $db           = new Database(DB_DATABASE);
        $descriptions = ProductDescription::where('description', '')->where('description', 'NOT LIKE', '% ')->pluck('product_id');
        $images       = Product::where('image', '')->orWhere('image', 'catalog/products/no-image.jpg')->pluck('product_id');
        $insert       = [];

        foreach ($descriptions as $item) {
            $insert[$item]['description'] = 0;
        }

        foreach ($images as $item) {
            $insert[$item]['image'] = 0;
        }

        LuceedProductForRevision::truncate();
        $products  = Product::whereIn('product_id', collect($descriptions)->merge($images)->unique())->get();
        $query_str = '';

        foreach ($products as $product) {
            $has_image       = isset($insert[$product->product_id]['image']) ? 0 : 1;
            $has_description = isset($insert[$product->product_id]['description']) ? 0 : 1;

            $query_str .= '("' . $product->luceed_uid . '", "' . $product->sku . '", "' . $db->escape($product->description(2)->first()->name) . '", ' . $has_image . ', ' . $has_description . ', 0, "", NOW(), NOW()),';
        }

        try {
            $db->query("INSERT INTO " . DB_PREFIX . "product_luceed_revision (uid, sku, `name`, has_image, has_description, resolved, `data`, date_added, date_modified) VALUES " . substr($query_str, 0, -1) . ";");
        } catch (\Exception $exception) {
            Log::store($exception->getMessage());
        }

        return $products->count();
    }


    /**
     * Collect, make and sort the data
     * for 1 products to make.
     *
     * @param $product
     *
     * @return array
     */
    public function make($product): array
    {
        $product      = collect($product);
        $manufacturer = ProductHelper::getManufacturer($product);
        $stock_status = $product['raspolozivo_kol'] ? agconf('import.default_stock_full') : agconf('import.default_stock_empty');
        $status       = 1;

        $description = ProductHelper::getDescription($product);

        if ( ! $product['opis'] || empty($product['dokumenti'])) {
            $status = 0;
        }

        if ($product['enabled'] == 'N') {
            $status = 0;
        }

        $images     = ProductHelper::getImages($product);
        $image_path = isset($images[0]['image']) ? $images[0]['image'] : agconf('import.image_placeholder');
        unset($images[0]);

        $prod = [
            'model'               => $product['artikl'],
            'sku'                 => '',
            'luceed_uid'          => $product['artikl_uid'],
            'upc'                 => $product['barcode'],
            'ean'                 => '',
            'jan'                 => '',
            'isbn'                => '',
            'mpn'                 => '',
            'location'            => '',
            'price'               => $product['mpc'],
            'tax_class_id'        => agconf('import.default_tax_class'),
            'quantity'            => $product['raspolozivo_kol'],
            'minimum'             => 1,
            'subtract'            => 1,
            'stock_status_id'     => $stock_status,
            'shipping'            => 1,
            'date_available'      => Carbon::now()->subDay()->format('Y-m-d'),
            'length'              => '',
            'width'               => '',
            'height'              => '',
            'length_class_id'     => 1,
            'weight'              => '',
            'weight_class_id'     => 1,
            'status'              => $status,
            'sort_order'          => substr($product['sezona_uid'], 0, strpos($product['sezona_uid'], '-')),
            'manufacturer'        => $manufacturer['name'],
            'manufacturer_id'     => $manufacturer['id'],
            'category'            => '',
            'filter'              => '',
            'download'            => '',
            'related'             => '',
            'image'               => $image_path,
            'points'              => '',
            'product_store'       => [0 => 0],
            //'product_attribute'   => ProductHelper::getAttributes($product),
            'product_description' => $description,
            'product_image'       => $images,
            'product_layout'      => [0 => ''],
            'product_category'    => ProductHelper::getCategories($product),
            'product_seo_url'     => [0 => ProductHelper::getSeoUrl($product)],
            'product_option'      => ProductHelper::getOptions($product),
        ];

        return $prod;
    }


    /**
     * Return the corrected response from luceed service.
     * Without unnecessary tags.
     *
     * @param $products
     *
     * @return array
     */
    private function setProducts($products): array
    {
        $prods = json_decode($products);

        return $prods->result[0]->artikli;
    }


    /**
     * @return $this
     */
    public function collectImages()
    {
        $full_list = $this->getProducts()
                          ->where('artikl', '!=', '')
                          ->where('naziv', '!=', '')
                          ->where('enabled', '!=', 'N')
                          ->where('webshop', '!=', 'N')
                          ->where('osnovni__artikl', '==', null);

        //$full_list_2 = $full_list->splice(1300);

        Log::store($full_list->count(), 'aa_fotke');
        //Log::store($full_list_2->count(), 'aa_fotke');

        foreach ($full_list as $item) {
            $this->updateImages($item);
        }

        return $this;
    }


    /**
     * @return int
     */
    public function collectImage(): int
    {
        $item = $this->getProducts()->first();

        if ($item) {
            $stored = $this->updateImages($item);

            if ($stored) {
                return 1;
            }
        }

        return 0;
    }


    /**
     * @param $item
     *
     * @return bool
     */
    public function updateImages($item): bool
    {
        if ($item) {
            $images     = ProductHelper::getImages(collect($item));
            $image_path = isset($images[0]['image']) ? $images[0]['image'] : agconf('import.image_placeholder');
            $product    = Product::query()->where('model', $item->artikl)->first();
            unset($images[0]);

            //Log::store($product->toArray(), 'aa_single');

            if ($product) {
                $db = new Database(DB_DATABASE);

                $product->update([
                    'image' => $image_path
                ]);

                $db->query("DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product->product_id . "'");

                foreach ($images as $product_image) {
                    $db->query("INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int) $product->product_id . "', image = '" . $db->escape($product_image['image']) . "', sort_order = '" . (int) $product_image['sort_order'] . "'");
                }

                return true;
            }
        }

        return false;
    }
}