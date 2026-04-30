<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class faqcontent extends Model
{
    use HasFactory;
    protected $table = 'faqcontent';
    protected $fillable = ['id','service_category_id','slug', 'name','description','is_archive', 'created_by','created_at', 'updated_by','updated_at'];

    public function serviceCategoryDetail()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }
}
