<?php


namespace Agmedia\Models\Attribute;


use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    
    /**
     * @var string
     */
    protected $table = 'attribute';
    
    /**
     * @var string
     */
    protected $primaryKey = 'attribute_id';
    
    /**
     * @var array
     */
    protected $guarded = [
        'attribute_id'
    ];
    
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function descriptions()
    {
        return $this->hasMany(AttributeDescription::class, 'attribute_id', 'attribute_id');
    }
    
    
    /**
     * @param $language_id
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function description($language_id)
    {
        return $this->hasOne(AttributeDescription::class, 'attribute_id', 'attribute_id')->where('language_id', $language_id);
    }

}