<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasFactory;
    protected $table = 'faq';
    protected $fillable = ['id','service_category_id','slug', 'name','description','is_archive', 'created_by','created_at', 'updated_by','updated_at'];

    public function serviceCategoryDetail()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }
}
