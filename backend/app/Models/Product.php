<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $table = 'products';
    protected $fillable = ['slug', 'shop_category_id', 'name', 'sku', 'description', 'specification', 'price', 'meta_title', 'meta_keywords', 'meta_description', 'amazon_link', 'flipcart_link', 'is_archive', 'status', 'created_by', 'updated_by'];

    public function shopCategoryDetail(){
        return $this->belongsTo(ShopCategory::class, 'shop_category_id')->where([['is_archive', \Constant::NOT_ARCHIVE], ['status', \Constant::ACTIVE]]);
    }

    public function images(){
        return $this->hasMany(ProductImage::class, 'product_id');
    }

    public function primaryImage(){
        return $this->hasOne(ProductImage::class, 'product_id')->where([['is_primary', 1]]);
    }
}
