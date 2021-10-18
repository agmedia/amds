<?php

namespace Agmedia\Luceed\Models;

use Illuminate\Database\Eloquent\Model;

class LuceedProductForRevisionData extends Model
{

    /**
     * @var string
     */
    protected $table = 'product_luceed_revision_data';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var bool
     */
    public $timestamps = false;

}