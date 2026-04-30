<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetails extends Model
{
    use HasFactory;
    protected $table = 'order_details';
    protected $fields = ['order_id', 'service_id', 'product_id', 'price', 'qty', 'subtotal'];

    public function orderDetail()
    {
        return $this->belongsTo(Order::class,'order_id');
    }

    public function productDetail()
    {
        return $this->belongsTo(Product::class,'product_id');
    }

    public function packageDetail(){
        return $this->belongsTo(ScheduledPackageDetail::class, 'service_id')->with('packageDetail','brandDetail', 'modelDetail', 'fuelTypeDetail');
    }
}
