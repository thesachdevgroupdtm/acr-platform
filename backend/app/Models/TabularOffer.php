<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TabularOffer extends Model
{
    use HasFactory;
    protected $table = 'tabular_offer';
    protected $fill = [ 'title','image_url','link','updated_by','created_by'];
}
