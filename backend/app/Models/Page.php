<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    protected $table = 'pages';
    protected $fillable = ['slug', 'name', 'description', 'meta_keyword','meta_title','extra_meta_tag', 'meta_description','canonical_tag'];
}
