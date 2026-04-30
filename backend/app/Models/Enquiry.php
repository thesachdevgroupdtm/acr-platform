<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enquiry extends Model
{
    use HasFactory;
    protected $table = 'enquires';
    protected $fillable = ['id','name','email','phone','service','location','message','is_archive','created_by','created_at','updated_by','updated_at'];

    public function serviceCategory()
    {
        return $this->belongsTo(ServiceCategory::class, 'service');
    }
}
