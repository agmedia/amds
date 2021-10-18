<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Database;
use Agmedia\Helpers\Log;
use Agmedia\Kaonekad\AttributeHelper;
use Agmedia\Kaonekad\ScaleHelper;
use Agmedia\Luceed\Models\LuceedProduct;
use Agmedia\Luceed\Models\LuceedProductForRevision;
use Agmedia\Luceed\Models\LuceedProductForUpdate;
use Agmedia\LuceedOpencartWrapper\Helpers\ProductHelper;
use Agmedia\Models\Product\Product;
use Agmedia\Models\Product\ProductDescription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Class LOC_Product
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class LOC_ProductSingle
{

    /**
     * @var array
     */
    public $product = null;

    /**
     * @var Collection
     */
    public $product_to_update = null;

    /**
     * @var Collection
     */
    public $product_to_insert = null;

    /**
     * @var Collection
     */
    public $product_to_delete = null;

    /**
     * @var Collection
     */
    private $luceed_product = null;

    /**
     * @var string
     */
    private $hash = '';

    /**
     * @var array
     */
    private $products_for_revision = [];


    /**
     * LOC_ProductSingle constructor.
     */
    public function __construct($product = null)
    {
        if ($product) {
            $this->product = $this->setProduct($product);
        }
    }


    /**
     * @return Collection
     */
    public function resolveLuceedProductData(): Collection
    {
        return collect(unserialize(base64_decode($this->luceed_product->data)));
    }


    /**
     * @return bool
     */
    public function hasForUpdate(): bool
    {
        $uid = LuceedProductForUpdate::all()->first();

        if ($uid) {
            $this->product_to_update = Product::where('luceed_uid', $uid->uid)->first();
            $this->luceed_product = LuceedProduct::query()->where('uid', $uid->uid)->first();

            if ($this->product_to_update && $this->luceed_product) {
                $this->product = $this->resolveLuceedProductData();

                return true;
            }
        }

        return false;
    }


    /**
     * @param \stdClass $product
     */
    public function setForUpdate(array $product)
    {
        $this->hash = sha1(collect($product)->toJson());
        $this->product_to_update = Product::where('luceed_uid', $product['artikl_uid'])->first();

        if ($this->product_to_update) {
            $this->product = collect($product);
        } else {
            $this->product_to_insert = collect($product);
        }

        return $this;
    }


    /**
     * @return bool
     */
    public function hasForInsert(): bool
    {
        $existing = Product::pluck('luceed_uid');
        $diff     = LuceedProduct::whereNotIn('uid', $existing)->first();

        if ($diff) {
            $this->luceed_product = $diff;

            if ($this->luceed_product) {
                $this->product = $this->resolveLuceedProductData();

                return true;
            }
        }

        return false;
    }


    /**
     * @return bool
     */
    public function hasForDelete()
    {
        $luceed = LuceedProduct::pluck('sifra');
        $existing = Product::pluck('sku');

        $diff = $existing->diff($luceed);

        if ($diff->count()) {
            $this->product_to_delete = Product::where('sku', $diff->first())->first();

            return true;
        }

        return false;
    }


    /**
     * @return int
     */
    public function getDeleteProductId(): int
    {
        if ($this->product_to_delete) {
            return $this->product_to_delete['product_id'];
        }

        return 0;
    }


    /**
     * @return int
     */
    public function deleteFromRevision()
    {
        if ($this->product_to_delete) {
            return LuceedProductForRevision::where('sku', $this->product_to_delete['sku'])->delete();
        }

        return 0;
    }


    /**
     * @return array
     */
    public function finishDelete(): array
    {
        return [
            'status'  => 200,
            'message' => 'deleted'
        ];
    }


    /**
     * @return array
     */
    public function finishInsert(): array
    {
        $this->markChecked('insert');

        return [
            'status'  => 200,
            'message' => 'inserted'
        ];
    }


    /**
     * @return mixed
     */
    public function finishUpdate(): array
    {
        if ($this->product_to_update) {
            $this->markChecked();

            return [
                'status'  => 200,
                'message' => 'updated'
            ];
        }
    }


    /**
     * @return mixed
     */
    public function finishUpdateError(): array
    {
        $this->pushToRevision();
        $this->markChecked();

        return [
            'status'  => 200,
            'message' => 'error'
        ];
    }


    /**
     * @return array
     */
    public function finish(): array
    {
        return [
            'status'  => 200,
            'message' => 'finish'
        ];
    }


    /**
     * @param array $old_product
     *
     * @return array
     */
    public function makeForUpdate(array $old_product): array
    {
        if ( ! $this->product) {
            return false;
        }

        $product                     = $this->make();
        $product['product_discount'] = $old_product['product_discount'];
        $product['product_special']  = $old_product['product_special'];
        $product['product_download'] = $old_product['product_download'];
        $product['product_filter']   = $old_product['product_filter'];
        $product['product_related']  = $old_product['product_related'];
        $product['product_reward']   = $old_product['product_reward'];

        return $product;
    }


    /**
     * @return array
     */
    public function makeForInsert(): array
    {
        if ( ! $this->product) {
            return [];
        }

        return $this->make();
    }


    /**
     * Collect, make, sort the data for 1 product.
     *
     * @param $product
     *
     * @return array
     */
    public function make(): array
    {
        $manufacturer = ProductHelper::getManufacturer($this->product);
        $stock_status = $this->product['stanje_kol'] ? agconf('import.default_stock_full') : agconf('import.default_stock_empty');
        $status       = 1;

        $description = ProductHelper::getDescription($this->product);

        if ($this->product_to_update) {
            $old_description = ProductDescription::where('product_id', $this->product_to_update->product_id)
                                                 ->where('language_id', agconf('import.default_language'))
                                                 ->first();

            $description = ProductHelper::getDescription($this->product, $old_description);
        }

        if ( ! $this->product['opis'] || empty($this->product['dokumenti'])) {
            $status = 0;
            $this->pushToRevision();
        }

        if ($this->product['enabled'] == 'N') {
            $status = 0;
        }

        $attributes = ProductHelper::getAttributes($this->product);
        $images = ProductHelper::getImages($this->product);
        $image_path = isset($images[0]['image']) ? $images[0]['image'] : 'image/placeholder.png';
        unset($images[0]);

        $prod = [
            'model'               => $this->product['artikl'],
            'sku'                 => $this->product['artikl'],
            'luceed_uid'          => $this->product['artikl_uid'],
            'upc'                 => $this->product['barcode'],
            'ean'                 => '',
            'jan'                 => '',
            'isbn'                => '5',
            'mpn'                 => $this->product['jamstvo_naziv'] ?: '',
            'location'            => '',
            'price'               => $this->product['mpc'],
            'price_2'             => $this->product['mpc'],
            'tax_class_id'        => agconf('import.default_tax_class'),
            'quantity'            => $this->product['stanje_kol'] ?: 0,
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
            'sort_order'          => 0,
            'manufacturer'        => $manufacturer['name'],
            'manufacturer_id'     => $manufacturer['id'],
            'category'            => '',
            'filter'              => '',
            'download'            => '',
            'related'             => '',
            'image'               => ! empty($this->product['dokumenti']) ? $image_path : agconf('import.image_placeholder'),
            'points'              => '',
            'product_store'       => [0 => 0],
            'product_attribute'   => $attributes,
            'product_description' => $description,
            'product_image'       => $images,
            'product_layout'      => [0 => ''],
            'product_category'    => ProductHelper::getCategories($this->product),
            'product_seo_url'     => [0 => ProductHelper::getSeoUrl($this->product)],
        ];

        return $prod;
    }


    /**
     *
     */
    private function pushToRevision(): void
    {
        $this->product['data'] = '';

        if ($this->product_to_update) {
            $this->product['product_id'] = $this->product_to_update->product_id;

            if ( ! isset($this->product['naziv'])) {
                $this->product['naziv'] = ProductDescription::where('product_id', $this->product_to_update->product_id)->pluck('name')->first();
                $this->product['artikl_uid'] = $this->product_to_update['luceed_uid'];
                $this->product['artikl'] = $this->product_to_update->sku;
                $this->product['data'] = 'Error importing Luceed data..!';
            }
        }

        $this->product['has_image'] = 0;
        $this->product['has_description'] = 0;

        if (isset($this->product['opis']) && $this->product['opis']) {
            $this->product['has_description'] = 1;
        }

        if ( ! empty($this->product['dokumenti'])) {
            $this->product['has_image'] = 1;
        }

        $has = LuceedProductForRevision::where('uid', $this->product['artikl_uid'])->first();

        if ( ! $has) {
            LuceedProductForRevision::insert([
                'uid' => $this->product['artikl_uid'],
                'sku' => $this->product['artikl'],
                'name' => $this->product['naziv'],
                'has_image' => $this->product['has_image'],
                'has_description' => $this->product['has_description'],
                'resolved' => 0,
                'data' => $this->product['data'],
                'date_added' => Carbon::now(),
                'date_modified' => Carbon::now(),
            ]);
        }
    }


    /**
     *
     */
    private function checkHash(): void
    {
        if ($this->luceed_product) {
            $this->hash = $this->luceed_product->hash;
        }
    }


    /**
     * @return mixed
     */
    private function markChecked(string $type = null)
    {
        $imported = 0;

        if ($type) {
            $imported = 1;
        }

        $this->checkHash();

        return Product::where('luceed_uid', $this->product['artikl_uid'])->update([
            'updated' => 1,
            'imported' => $imported,
            'hash'    => $this->hash
        ]);
    }


    /**
     * Return the corrected response from luceed service.
     * Without unnecessary tags.
     *
     * @param $products
     *
     * @return array
     */
    private function setProduct($product): array
    {
        $prods = json_decode($product);

        return $prods->result[0]->artikli[0];
    }
}