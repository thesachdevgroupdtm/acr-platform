<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageSpecification extends Model
{
    use HasFactory;

    protected $table = 'package_specification';
    protected $fillable = ['sp_id', 'specification', 'created_at', 'updated_at'];

}
