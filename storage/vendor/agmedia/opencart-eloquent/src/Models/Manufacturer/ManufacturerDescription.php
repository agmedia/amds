<?php


namespace Agmedia\Models\Manufacturer;


use Agmedia\Models\Product\Product;
use Illuminate\Database\Eloquent\Model;

class ManufacturerDescription extends Model
{
    
    /**
     * @var string
     */
    protected $table = 'manufacturer_description';

    /**
     * @var string
     */
    protected $primaryKey = 'manufacturer_id';

}