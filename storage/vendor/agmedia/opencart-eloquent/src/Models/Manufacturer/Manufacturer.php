<?php


namespace Agmedia\Models\Manufacturer;


use Agmedia\Models\Product\Product;
use Illuminate\Database\Eloquent\Model;

class Manufacturer extends Model
{
    
    /**
     * @var string
     */
    protected $table = 'manufacturer';

    /**
     * @var string
     */
    protected $primaryKey = 'manufacturer_id';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $guarded = [
        'manufacturer_id'
    ];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function descriptions()
    {
        return $this->hasMany(ManufacturerDescription::class, 'manufacturer_id', 'manufacturer_id');
    }


    /**
     * @param $language_id
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function description($language_id)
    {
        return $this->hasOne(ManufacturerDescription::class, 'manufacturer_id', 'manufacturer_id')->where('language_id', $language_id);
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'manufacturer_id', 'manufacturer_id');
    }
}