<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;
    protected $table = 'cart';
    protected $fillable = ['id','user_id', 'product_id', 'service_id','qty','created_at','updated_at'];

    public function productDetail(){
        return $this->belongsTo(Product::class, 'product_id')->with('primaryImage');
    }

    public function serviceDetail(){
        return $this->belongsTo(ScheduledPackageDetail::class, 'service_id')->with('packageDetail','brandDetail', 'modelDetail', 'fuelTypeDetail');
    }
}
