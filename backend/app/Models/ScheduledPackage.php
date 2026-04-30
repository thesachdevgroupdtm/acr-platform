<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledPackage extends Model
{
    use HasFactory;

    protected $table = 'sceduled_packages';
    protected $fillable = ['sc_id', 'slug', 'brand_id', 'model_id', 'fuel_type_id', 'title', 'image', 'image_other', 'warrenty_info', 'recommended_info', 'time_takes', 'time_takes_day', 'time_takes_option', 'price', 'is_archive', 'status', 'created_at', 'created_by', 'updated_at', 'updated_by'];

    public function categoryDetail(){
        return $this->belongsTo(ServiceCategory::class, 'sc_id');
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

    public function specifications(){
        return $this->hasMany(PackageSpecification::class, 'sp_id');
    }
}
