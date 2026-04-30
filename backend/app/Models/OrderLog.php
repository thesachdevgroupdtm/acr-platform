<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderLog extends Model
{
    use HasFactory;
    protected $table = 'order_logs';
    protected $fields = ['user_id', 'is_guest_chekout', 'payment_type', 'name', 'email', 'phone', 'address', 'zip', 'city', 'subtotal', 'product_gst_rate', 'service_gst_rate', 'product_gst', 'service_gst', 'total', 'order_date'];

    public function userData()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function detail()
    {
        return $this->hasMany(OrderDetailLog::class, 'order_id')->with('productDetail', 'packageDetail');
    }

    public function slotDetail()
    {
        return $this->hasOne(BookedSlotLog::class, 'order_id');
    }
}
