<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrandLogoSlider extends Model
{
    use HasFactory;
    protected $table = "brand_logo_slider";
    protected $fill = ['id','image'];
}
