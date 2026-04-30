<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopCategory extends Model
{
    use HasFactory;

    protected $table = 'shop_categories';
    protected $fillable = ['id','slug', 'name', 'is_archive', 'status', 'created_by','created_at', 'updated_by','updated_at'];

    public function products(){
        return $this->hasMany(Product::class, 'shop_category_id');
    }
}
