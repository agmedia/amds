<?php


namespace Agmedia\LuceedOpencartWrapper\Models;


use Agmedia\Helpers\Log;
use Agmedia\Models\Manufacturer\Manufacturer;
use Agmedia\Models\Manufacturer\ManufacturerDescription;
use Agmedia\Models\Manufacturer\ManufacturerToStore;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Class LOC_Manufacturer
 * @package Agmedia\LuceedOpencartWrapper\Models
 */
class LOC_Manufacturer
{

    /**
     * @var array|null
     */
    private $manufacturers;

    /**
     * @var null
     */
    private $manufacturers_to_add = null;
    
    
    /**
     * LOC_Manufacturer constructor.
     *
     * @param null $manufacturers
     */
    public function __construct($manufacturers = null)
    {
        $this->manufacturers = $manufacturers ? $this->setManufacturers($manufacturers) : null;
    }
    
    
    /**
     * @return Collection|null
     */
    public function getManufacturers(): ?Collection
    {
        return $this->manufacturers ? collect($this->manufacturers) : [];
    }
    
    
    /**
     * @return Collection|null
     */
    public function getManufacturersToAdd(): ?Collection
    {
        return $this->manufacturers_to_add ? collect($this->manufacturers_to_add) : [];
    }
    
    
    /**
     * @param $products
     *
     * @return $this
     */
    public function getFromProducts($products)
    {
        $list = json_decode($products)->result[0]->artikli;
        
        $this->manufacturers = collect($list)->unique('robna_marka')->toArray();
        
        return $this;
    }
    
    
    /**
     * @return $this
     */
    public function checkDiff()
    {
        if ($this->manufacturers) {
            $existing = Manufacturer::pluck('luceed_uid');
            $list_diff = $this->getManufacturers()
                ->where('enabled', 'D')
                ->where('robna_marka', '!=', '')
                ->where('naziv', '!=', '')
                ->pluck('robna_marka')
                ->diff($existing)
                ->flatten();
    
            $this->manufacturers_to_add = $this->getManufacturers()->whereIn('robna_marka', $list_diff);
        }
        
        return $this;
    }
    
    
    /**
     * @return int
     */
    public function import()
    {
        $count = 0;

        foreach ($this->getManufacturersToAdd() as $manufacturer) {
            $this->save($manufacturer);
            $count++;
        }

        return $count;
    }


    /**
     * @return int
     */
    public function initialImport()
    {
        $list = $this->load();
        $count = 0;

        if ( ! empty($list)) {
            foreach ($list['brands'] as $item) {
                $manufacturer = Manufacturer::where('luceed_uid', $item['robna_marka'])->first();

                if ($manufacturer && $item['logo'] != '') {
                    $url = 'https://www.chipoteka.hr' . str_replace(';', '', $item['logo']);

                    $newstring = substr($item['logo'], -3);

                    if($newstring=='png'  ){
                        $img = 'catalog/brands/' . Str::slug($item['name'] ?: $item['robna_marka_naziv']) . '.png';
                    }
                    elseif ($newstring=='PNG'){
                        $img = 'catalog/brands/' . Str::slug($item['name'] ?: $item['robna_marka_naziv']) . '.PNG';
                    }
                    else{
                        $img = 'catalog/brands/' . Str::slug($item['name'] ?: $item['robna_marka_naziv']) . '.jpg';
                    }



                    file_put_contents(DIR_IMAGE . $img, file_get_contents($url));

                    Manufacturer::where('manufacturer_id', $manufacturer->manufacturer_id)->update([
                        'image' => $img
                    ]);

                    ManufacturerDescription::insert([
                        'manufacturer_id' => $manufacturer->manufacturer_id,
                        'language_id' => 2,
                        'description' => $item['description'],
                        'meta_title' => $item['meta_title'],
                        'meta_description' => $item['meta_description'] ? Str::limit($item['meta_description'], 150) : Str::limit($item['description'], 150),
                        'meta_keyword' => $item['name'],
                    ]);

                    $count++;
                }
            }
        }

        return $count;
    }


    /**
     * @return array|Collection
     */
    public function load()
    {
        $file = json_decode(file_get_contents(DIR_STORAGE . 'upload/assets/brands.json'),TRUE);

        if ($file) {
            return collect($file);
        }

        return [];
    }
    
    
    /**
     * @param $manufacturer
     *
     * @return mixed
     */
    private function save($manufacturer)
    {
        $id = Manufacturer::insertGetId([
            'luceed_uid' => $manufacturer->robna_marka,
            'name' => $manufacturer->naziv,
            'image' => '',
            'sort_order' => 0
        ]);
        
        ManufacturerToStore::insert([
            'manufacturer_id' => $id,
            'store_id' => 0
        ]);
        
        return $id;
    }
    
    
    /**
     * @param $categories
     *
     * @return array
     */
    public function setManufacturers($manufacturers): array
    {
        $man = json_decode($manufacturers);
        
        return $man->result[0]->robne_marke;
    }
}