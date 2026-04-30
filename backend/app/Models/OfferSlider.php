<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfferSlider extends Model
{
    use HasFactory;
    protected $table = 'offer_slider';
    protected $fill = ['image', 'title1', 'title2', 'btn_title', 'btn_link', 'is_archive','reorder','membership_package','background','title_color','subtitle_color'];
}
