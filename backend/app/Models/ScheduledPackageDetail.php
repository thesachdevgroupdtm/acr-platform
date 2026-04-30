<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledPackageDetail extends Model
{
    use HasFactory;

    protected $table = 'scheduled_package_detail';
    protected $fillable = ['sp_id', 'brand_id', 'model_id', 'fuel_type_id', 'price', 'created_at', 'updated_at'];

    public function packageDetail(){
        return $this->belongsTo(ScheduledPackage::class, 'sp_id')->with('categoryDetail');
    }

    public function brandDetail(){
        return $this->belongsTo(CarBrand::class, 'brand_id');
    }

    public function modelDetail(){
        return $this->belongsTo(CarModel::class, 'model_id');
    }

    public function fuelTypeDetail(){
        return $this->belongsTo(FuelType::class, 'fuel_type_id');
    }
}
