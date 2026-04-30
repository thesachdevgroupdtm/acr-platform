<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TabServiceCmsPage extends Model
{
    use HasFactory;

    protected $table = 'tab_service_cms_page';
    protected $fillable = ['name','slug', 'image_title', 'banner_text', 'description', 'meta_keywords','meta_title', 'extra_meta_tag', 'meta_description','canonical_tag'];
}
