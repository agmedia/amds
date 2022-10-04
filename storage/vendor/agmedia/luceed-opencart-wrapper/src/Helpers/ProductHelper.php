<?php

namespace Agmedia\LuceedOpencartWrapper\Helpers;

use Agmedia\Helpers\Log;
use Agmedia\Kaonekad\AttributeHelper;
use Agmedia\Kaonekad\ScaleHelper;
use Agmedia\Luceed\Facade\LuceedProduct;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Category;
use Agmedia\Models\Attribute\Attribute;
use Agmedia\Models\Attribute\AttributeDescription;
use Agmedia\Models\Category\Category;
use Agmedia\Models\Manufacturer\Manufacturer;
use Agmedia\Models\Option\OptionValue;
use Agmedia\Models\Option\OptionValueDescription;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Class LOC_Product
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class ProductHelper
{

    /**
     * Traverse through opencart categories tree
     * and sort the response array.
     * If "grupa_artikla" luceed tag is not found in opencart DB,
     * default category is returned.
     *
     * @param Collection $product
     *
     * @return array
     */
    public static function getCategories(Collection $product): array
    {
        $lc = new LOC_Category();
        $response = [0 => agconf('import.default_category')];
        $actual   = Category::where('luceed_uid', $product['spol'] . '-' . $product['grupa_artikla_uid'])->first();

        if ( ! $actual) {
            $parent = Category::where('luceed_uid', $product['spol'] . '-' . $product['nadgrupa_artikla'])->first();

            if ( ! $parent) {
                $main = Category::where('luceed_uid', $product['spol_uid'])->first();

                if ( ! $main) {
                    $main_category = [];
                    $main_category['grupa_artikla'] = $product['spol_uid'];
                    $main_category['naziv'] = $product['spol_naziv'];

                    $main_id = $lc->save($main_category);
                    $main = Category::where('category_id', $main_id)->first();
                }

                $parent_category = [];
                $parent_category['grupa_artikla'] = $product['spol'] . '-' . $product['nadgrupa_artikla'];
                $parent_category['naziv'] = $product['nadgrupa_artikla_naziv'];

                $parent_id = $lc->save($parent_category, $main->category_id);
                $parent = Category::where('category_id', $parent_id)->first();
            }

            $actual_category = [];
            $actual_category['grupa_artikla'] = $product['spol'] . '-' . $product['grupa_artikla_uid'];
            $actual_category['naziv'] = $product['grupa_artikla_naziv'];

            $actual_id = $lc->save($actual_category, $parent->category_id);
            $actual   = Category::where('category_id', $actual_id)->first();
        }

        if ($actual && $actual->count()) {
            $response[0] = $actual->category_id;

            if ($actual->parent_id) {
                $parent = Category::where('category_id', $actual->parent_id)->first();

                if ($parent->count()) {
                    $response[1] = $parent->category_id;

                    if ($parent->parent_id) {
                        $main = Category::where('category_id', $parent->parent_id)->first();

                        if ($main->count()) {
                            $response[2] = $main->category_id;
                        }
                    }
                }
            }

            if ($product['marker']) {
                $response[7] = static::getStrukId($product, $actual->category_id);
            }

            if ($product['jamstvo']) {
                $response[8] = static::getNosivostId($product, $actual->category_id);
            }
        }

        if (static::hasOutletCategory(collect($product['atributi']))) {
            $response = [];
            $actual   = Category::where('luceed_uid', $product['spol'] . '-' . $product['grupa_artikla_uid'] . '-outlet')->first();

            if ( ! $actual) {
                $parent = Category::where('luceed_uid', $product['spol'] . '-' . $product['nadgrupa_artikla'] . '-outlet')->first();

                if ( ! $parent) {
                    $main = Category::where('luceed_uid', $product['spol_uid'] . '-outlet')->first();

                    if ( ! $main) {
                        $main_category = [];
                        $main_category['grupa_artikla'] = $product['spol_uid'] . '-outlet';
                        $main_category['naziv'] = $product['spol_naziv'];

                        $main_id = $lc->save($main_category, agconf('import.outlet_category'));
                        $main = Category::where('category_id', $main_id)->first();
                    }

                    $parent_category = [];
                    $parent_category['grupa_artikla'] = $product['spol'] . '-' . $product['nadgrupa_artikla'] . '-outlet';
                    $parent_category['naziv'] = $product['nadgrupa_artikla_naziv'];

                    $parent_id = $lc->save($parent_category, $main->category_id);
                    $parent = Category::where('category_id', $parent_id)->first();
                }

                $actual_category = [];
                $actual_category['grupa_artikla'] = $product['spol'] . '-' . $product['grupa_artikla_uid'] . '-outlet';
                $actual_category['naziv'] = $product['grupa_artikla_naziv'];

                $actual_id = $lc->save($actual_category, $parent->category_id);
                $actual   = Category::where('category_id', $actual_id)->first();
            }

            if ($actual && $actual->count()) {
                $response[3] = $actual->category_id;

                if ($actual->parent_id) {
                    $parent = Category::where('category_id', $actual->parent_id)->first();

                    if ($parent->count()) {
                        $response[4] = $parent->category_id;

                        if ($parent->parent_id) {
                            $main = Category::where('category_id', $parent->parent_id)->first();

                            if ($main->count()) {
                                $response[5] = $main->category_id;
                            }
                        }
                    }
                }

                $response[6] = agconf('import.outlet_category');
            }
        }

        $response[7] = 149;

      //  Log::store($response, 'Response_2022');
      //  Log::store(array_unique($response), 'Response_2022');

        $response = array_unique(array_values($response));


        return $response;
    }


    private static function getStrukId(Collection $product, int $parent)
    {
        $lc = new LOC_Category();
        $has = Category::where('luceed_uid', $product['marker_uid'] . '-' . $parent . '-s')->first();

        if ( ! $has) {
            $new = [];
            $new['grupa_artikla'] = $product['marker_uid'] . '-' . $parent . '-s';
            $new['naziv'] = $product['marker_naziv'];

            $has_id = $lc->save($new, $parent);
            $has = Category::where('category_id', $has_id)->first();
        }

        return $has->category_id;
    }


    private static function getNosivostId(Collection $product, int $parent)
    {
        $lc = new LOC_Category();
        $has = Category::where('luceed_uid', $product['jamstvo_uid'] . '-' . $parent . '-n')->first();

        if ( ! $has) {
            $new = [];
            $new['grupa_artikla'] = $product['jamstvo_uid'] . '-' . $parent . '-n';
            $new['naziv'] = $product['jamstvo_naziv'];

            $has_id = $lc->save($new, $parent);
            $has = Category::where('category_id', $has_id)->first();
        }

        return $has->category_id;
    }


    /**
     * @param array $categories
     *
     * @return array
     */
    public static function sortOutletCategory(array $categories): array
    {
        $categories[3] = agconf('import.outlet_category');

        if (isset($categories[2])) {
            $categories[4] = $categories[2];
            $categories[5] = $categories[1];

            return $categories;
        }

        $categories[4] = $categories[1];

        return array_values($categories);
    }


    /**
     * @param Collection $attributes
     *
     * @return bool
     */
    public static function hasOutletCategory(Collection $attributes): bool
    {
        foreach ($attributes as $attribute) {
            if ($attribute->atribut == 'outlet' && $attribute->vrijednost == 'D') {
                return true;
            }
        }

        return false;
    }


    /**
     * @param Collection $product
     *
     * @return array
     */
    public static function getManufacturer(Collection $product): array
    {
        if (isset($product['robna_marka'])) {
            $manufacturer = Manufacturer::where('luceed_uid', $product['robna_marka'])->first();

            if ($manufacturer) {
                return [
                    'id'   => $manufacturer->manufacturer_id,
                    'name' => $manufacturer->name
                ];
            }
        }

        return ['id' => 0, 'name' => ''];
    }


    /**
     * Return description with default language.
     * Language_id as response array key.
     *
     * @param Collection $product
     * @param null       $old_description
     *
     * @return array
     */
    public static function getDescription(Collection $product, $old_description = null): array
    {
        // Check if description exist.
        //If not add title for description.
        $naziv = $product['naziv'];
        $description = static::setDescription($product['opis']);

        if ($old_description) {
            if ( ! $old_description['update_name']) {
                $naziv = $old_description['name'];
            }
            if ( ! $old_description['update_description']) {
                $description = $old_description['description'];
            }
        }

        $response[agconf('import.default_language')] = [
            'name'              => $naziv,
            'description'       => $description,
            'short_description' => $description,
            'tag'               => $naziv,
            'meta_title'        => $naziv,
            'meta_description'  => strip_tags($description),
            'meta_keyword'      => $naziv,
        ];

        return $response;
    }


    /**
     * @return array
     */
    public static function getAttributes(Collection $product): array
    {
        $response   = [];
        $attributes = collect($product['atributi']);

        foreach ($attributes as $attribute) {
            $attribute = collect($attribute);
            if (static::checkAttributeForImport($attribute)) {
                $has = Attribute::where('luceed_uid', $attribute['atribut_uid'])->first();

                if ($has && $has->count()) {
                    $id = $has['attribute_id'];
                } else {
                    $id = static::makeAttribute($attribute);
                }

                if ($id) {
                    $response[] = [
                        'attribute_id' => $id,
                        'product_attribute_description' => [
                            agconf('import.default_language') => [
                                'text' => $attribute['vrijednost']
                            ]
                        ]
                    ];
                }
            }
        }

        return $response;
    }


    /**
     * @return array
     */
    public static function getSeoUrl(Collection $product): array
    {
        $slug = Str::slug($product['naziv']) . '-' . $product['artikl'];

        return [
            agconf('import.default_language') => $slug
        ];
    }


    /**
     * Get the image string from luceed service and
     * return the full path string.
     *
     * @param Collection $product
     * @param int        $key
     *
     * @return string
     */
    public static function getImagePath($product, string $naziv, string $uid): string
    {
        if ($product) {
            $image_path = agconf('import.image_path') . $uid . '/';
            // Check if the image path exist.
            // Create it if not.
            if ( ! is_dir(DIR_IMAGE . $image_path)) {
                mkdir(DIR_IMAGE . $image_path, 0777, true);
            }

            if (isset($product['filename'])) {
                $newstring = substr($product['filename'], -3);
            } else {
                $newstring = substr($product['filename'], -3);
            }

            $name = Str::slug($naziv) . '-' . strtoupper(Str::random(9)) . '.jpg';

            if (in_array($newstring, ['png', 'PNG'])) {
                $name = Str::slug($naziv) . '-' . strtoupper(Str::random(9)) . '.' . $newstring;
            }

            // Setup and create the image with GD library.
            $bin   = base64_decode(static::getImageString($product));

            if ($bin) {
                $errorlevel=error_reporting();
                error_reporting(0);
                $image = imagecreatefromstring($bin);
                error_reporting($errorlevel);

                if ($image !== false) {
                    imagejpeg($image, DIR_IMAGE . $image_path . $name, 90);

                    // Return only the image path.
                    return $image_path . $name;
                }
            }
        }

        return 'not_valid_image';
    }


    /**
     * @param Collection $product
     *
     * @return array
     */
    public static function getImages(Collection $product): array
    {
        $response = [];
        $docs  = collect($product['dokumenti']);

        if ($docs->count()) {
            foreach ($docs as $doc) {
                $doc = collect($doc)->toArray();

                if (isset($doc['file_uid']) && substr($doc['filename'], -3) != 'pdf') {
                    if (isset($doc['file_uid'])) {
                        $uid = $doc['file_uid'];
                    } else {
                        $uid = $doc['file_uid'];
                    }

                    $response[] = [
                        'uid'        => $uid,
                        'md5'        => $doc['md5'],
                        'image'      => static::getImagePath($doc, $product['naziv'], $product['artikl_uid']),
                        'sort_order' => isset($doc['redoslijed']) ? $doc['redoslijed'] : 0
                    ];
                }
            }
        }

        return $response;
    }


    /**
     * @param Collection $product
     *
     * @return array
     */
    public static function getOptions(Collection $product): array
    {
        $options_response = [];
        // Resolve option_id from scale. This is fixed and maped with OC_options DB.
        $option_id = 13;//ScaleHelper::resolveOptionId($scale);
        // Get the options with that option_id to compare it by name for option_value_id.
        // Also fixed and mapped with OC_options DB.
        $options = OptionValueDescription::where('option_id', $option_id)->get();

        $response[0] = [
            'value'     => '',
            'option_id' => $option_id,
            'type'      => 'radio',
            'required'  => 1
        ];

        // Sorting options and calculations for it's price.
        // Depending on scale option property
        // and it's default value.
        if (isset($product['opcije'])) {
            foreach ($product['opcije'] as $item) {
                // Find the option_value_id by it's name.
                $option_value_id = $options->where('name', $item['velicina_naziv'])->first();
                // If option not exist, make it.
                if ( ! $option_value_id) {
                    $oid = OptionValue::insertGetId([
                        'option_id' => 13,
                        'image' => '',
                        'sort_order' => 0
                    ]);

                    $ovid = OptionValueDescription::insertGetId([
                        'option_value_id' => $oid,
                        'language_id' => 2,
                        'option_id' => 13,
                        'name' => $item['velicina_naziv']
                    ]);

                    if ($ovid) {
                        $option_value_id = OptionValueDescription::where('name', $item['velicina_naziv'])->first();
                    }
                }

                if ($option_value_id) {
                    $options_response[] = [
                        'option_value_id' => $option_value_id->option_value_id,
                        'quantity'        => $item['raspolozivo_kol'],
                        'subtract'        => 0,
                        'price_prefix'    => '+',
                        'price'           => 0,
                        'points_prefix'   => '',
                        'points'          => '',
                        'weight_prefix'   => '+',
                        'sku'             => $item['uid'],
                        'sifra'           => $item['artikl'],
                        'weight'          => 0,
                    ];
                }
            }
        }

        $response[0]['product_option_value'] = $options_response;

        return $response;
    }


    /**
     * @param \stdClass $product
     *
     * @return array
     */
    public static function collectLuceedData(\stdClass $product): array
    {
        $atributi = [];
        $dokumenti = [];

        if ( ! empty($product->atributi)) {
            foreach ($product->atributi as $atr) {
                $atributi[] = [
                    'atribut_uid' => $atr->atribut_uid,
                    'naziv' => $atr->naziv,
                    'aktivan' => $atr->aktivan,
                    'vidljiv' => $atr->vidljiv,
                    'vrijednost' => $atr->vrijednost,
                ];
            }
        }

        if ( ! empty($product->dokumenti)) {
            foreach ($product->dokumenti as $dok) {
                $dokumenti[] = [
                    'file_uid' => $dok->file_uid,
                    'filename' => $dok->filename,
                    'naziv' => $dok->naziv,
                    'md5' => $dok->md5,
                ];
            }
        }

        return [
            'artikl_uid' => $product->artikl_uid,
            'artikl' => $product->artikl,
            'naziv' => $product->naziv,
            'barcode' => $product->barcode,
            'jm' => $product->jm,
            'opis' => static::setDescription($product->opis),
            'vpc' => $product->vpc,
            'mpc' => $product->mpc,
            'enabled' => $product->enabled,
            'specifikacija' => static::setDescription($product->specifikacija),
            'stopa_pdv' => $product->stopa_pdv,
            'nadgrupa_artikla' => $product->nadgrupa_artikla,
            'nadgrupa_artikla_naziv' => $product->nadgrupa_artikla_naziv,
            'grupa_artikla' => $product->grupa_artikla_uid,
            'grupa_artikla_naziv' => $product->grupa_artikla_naziv,
            'robna_marka' => $product->robna_marka,
            'robna_marka_naziv' => $product->robna_marka_naziv,
            'jamstvo_naziv' => $product->jamstvo_naziv,
            'stanje_kol' => $product->stanje_kol,
            'atributi' => $atributi,
            'dokumenti' => $dokumenti,
        ];
    }


    /**
     * @param array $product
     *
     * @return string
     */
    public static function hashLuceedData(array $product): string
    {
        unset($product['stanje_kol']);

        return sha1(
            collect($product)->toJson()
        );
    }


    /**
     * @param array $products
     *
     * @return array
     */
    public static function sortOptions(array $products): array
    {
        $response = [];

        foreach ($products as $product) {
            $response[] = [
                'uid' => $product->artikl_uid,
                'artikl' => $product->artikl,
                'barcode' => $product->barcode,
                'mpc' => $product->mpc,
                'velicina_uid' => $product->velicina_uid,
                'velicina' => $product->velicina,
                'velicina_naziv' => $product->velicina_naziv,
                'raspolozivo_kol' => $product->raspolozivo_kol
            ];
        }

        return $response;
    }


    /**
     * @param float $price
     * @param int   $discount
     *
     * @return float|int
     */
    public static function calculateDiscountPrice(float $price, int $discount)
    {
        if ( ! $discount) {
            return $price;
        }

        return $price - ($price * ($discount / 100));
    }


    /*******************************************************************************
    *                                Copyright : AGmedia                           *
    *                              email: filip@agmedia.hr                         *
    *******************************************************************************/

    /**
     * @param Collection $product
     *
     * @return array
     */
    private static function setCategoryForImport(Collection $product): array
    {
        $response = [];
        $response['grupa_artikla'] = $product['spol_uid'];
        $response['naziv'] = $product['spol_naziv'];

        return $response;
    }


    /**
     * @param string|null $text
     *
     * @return string
     */
    private static function setDescription(string $text = null): string
    {
        if ($text) {
            $text = str_replace("\n", '<br>', $text);
            $text = str_replace("\r", '<br>', $text);
            $text = str_replace("\t", '<tab>', $text);

            return $text;
        }

        return '';
    }


    /**
     * @param $attribute
     *
     * @return bool
     */
    private static function checkAttributeForImport($attribute): bool
    {
        if ($attribute['aktivan'] == 'D' &&
            $attribute['vidljiv'] == 'D' &&
            $attribute['atribut_uid'] != '' &&
            $attribute['naziv'] != '')
        {
            return true;
        }

        return false;
    }


    /**
     * @param $attribute
     *
     * @return false|int
     */
    private static function makeAttribute($attribute)
    {
        $id = Attribute::insertGetId([
            'luceed_uid' => $attribute['atribut_uid'],
            'attribute_group_id' => agconf('import.default_attribute_group'),
            'sort_order' => isset($attribute['redoslijed']) ? $attribute['redoslijed'] : 9
        ]);

        if ($id) {
            AttributeDescription::insert([
                'attribute_id' => $id,
                'language_id' => agconf('import.default_language'),
                'name' => $attribute['naziv']
            ]);

            return $id;
        }

        return false;
    }


    /**
     * Get the image string from luceed service.
     *
     * @param $product
     *
     * @return false
     */
    private static function getImageString($product)
    {
        if (isset($product['file_uid'])) {
            $uid = $product['file_uid'];
        } else {
            $uid = $product['file_uid'];
        }

        if (in_array($uid, ['108736-1063'])) {
            return false;
        }

        $result = LuceedProduct::getImage($uid);
        $image = json_decode($result);

        return $image->result[0]->files[0]->content;
    }

}