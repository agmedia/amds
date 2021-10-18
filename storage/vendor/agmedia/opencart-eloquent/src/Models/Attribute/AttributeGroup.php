<?php


namespace Agmedia\Models\Attribute;


use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

class AttributeGroup extends Model
{
    
    /**
     * @var string
     */
    protected $table = 'attribute_group';
    
    /**
     * @var string
     */
    protected $primaryKey = 'attribute_group_id';
    
    /**
     * @var array
     */
    protected $guarded = [
        'attribute_group_id'
    ];
    
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function descriptions()
    {
        return $this->hasMany(AttributeGroupDescription::class, 'attribute_group_id', 'attribute_group_id');
    }
    
    
    /**
     * @param $language_id
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function description($language_id)
    {
        return $this->hasOne(AttributeGroupDescription::class, 'attribute_group_id', 'attribute_group_id')->where('language_id', $language_id);
    }

}