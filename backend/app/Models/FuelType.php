<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuelType extends Model
{
    use HasFactory;
    protected $table = 'fuel_type';
    protected $fillable = ['slug', 'title', 'is_archive', 'status', 'created_by', 'updated_by'];
}
