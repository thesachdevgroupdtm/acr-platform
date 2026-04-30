<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarModel extends Model
{
    use HasFactory;

    protected $table = 'model';
    protected $fillable = ['slug', 'carbrand_id', 'title', 'image','is_archive', 'status'];
    
    public function brandDetail(){
        return $this->belongsTo(CarBrand::class, 'carbrand_id');
    }
}
