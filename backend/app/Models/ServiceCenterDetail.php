<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCenterDetail extends Model
{
    use HasFactory;
    protected $table = "service_center_detail";
    protected $fill = ['id', 'name', 'address', 'image', 'phone_number'];
}
