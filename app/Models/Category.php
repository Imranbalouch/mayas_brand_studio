<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class Category extends Model
{
    use HasFactory , LogsActivity;

    use LogsActivity;
    // public $incrementing = false; // Disable auto-incrementing IDs
    // protected $keyType = 'string'; // Set the key type to string (for UUIDs)
    // protected $primaryKey = 'uuid'; // Set the primary key to 'uuid'
    

    // protected $with = ['category_translations'];
public function childrenCategories()
{
    return $this->hasMany(Category::class, 'parent_id')->orderBy('order_level', 'ASC');
}
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('category')->logOnly(['id','name','parent_id','order_level','banner','icon','slug','meta_title','meta_description','created_at','updated_at']);
    }

    public function category_translations(){
    	return $this->hasMany(CategoryTranslation::class,'category_uuid','uuid');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_categories', 'category_uuid', 'product_uuid','uuid','uuid');
    }
    // public function categories()
    // {
    //     return $this->hasMany(Category::class, 'parent_id');
    // }

    // public function childrenCategories()
    // {
    //     return $this->hasMany(Category::class, 'parent_id')->with('categories');
    // }

    // public function parentCategory()
    // {
    //     return $this->belongsTo(Category::class, 'parent_id');
    // }

    // public function attributes()
    // {
    //     return $this->belongsToMany(Attribute::class);
    // }

    // public function productsCategory()
    // {
    //     return $this->hasMany(Product::class, 'category_id');
    // }

    protected $fillable = [
        'uuid',
        'parent_id',
        'level',
        'name',
        'order_level',
        'banner',
        'icon',
        'status',
        'featured',
        'slug',
        'meta_title',
        'auth_id',
        'meta_description',
    ];
    
    protected $hidden = [
        // 'id', 
        'commision_rate',
        'top',
        'digital'
    ];

    public function getTranslation($field = '', $lang = false){
        $lang = $lang == false ? getConfigValue('defaul_lang') : $lang;
        $category_translations = $this->category_translations->where('lang', $lang)->first();
        return $category_translations != null ? $category_translations->$field : $this->$field;
    }

}
