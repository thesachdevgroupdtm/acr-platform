<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seo extends Model
{
    use HasFactory;

    protected $table = 'seo';
    protected $fillable = ['id','meta_title', 'meta_keyword', 'meta_description','canonical_tag','extra_meta_description', 'is_archive', 'created_at','updated_at'];
}
