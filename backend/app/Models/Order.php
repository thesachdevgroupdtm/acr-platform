<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $table = 'orders';
    protected $fields = ['user_id', 'is_guest_chekout', 'payment_type', 'name', 'email', 'phone', 'address', 'zip', 'city', 'subtotal', 'product_gst_rate', 'service_gst_rate', 'product_gst', 'service_gst', 'total', 'order_date'];

    public function userData()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function detail()
    {
        return $this->hasMany(OrderDetails::class, 'order_id')->with('productDetail', 'packageDetail');
    }

    public function slotDetail()
    {
        return $this->hasOne(BookedSlot::class, 'order_id');
    }
}
