<?php

namespace Agmedia\Luceed\Models;

use Agmedia\Models\Product\Product;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

class LuceedProductForUpdate extends Model
{

    /**
     * @var string
     */
    protected $table = 'product_luceed_for_update';

    /**
     * @var string
     */
    protected $primaryKey = 'uid';

    /**
     * @var bool
     */
    public $timestamps = false;


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function product()
    {
        return $this->hasOne(Product::class, 'luceed_uid', 'uid');
    }

}