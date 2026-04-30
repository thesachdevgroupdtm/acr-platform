<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens;
    protected $table = 'users';
    protected $fillable = ['id','firstname','lastname','email','password','visible_password','phone','image','address','city','state','country','zipcode','status','is_archive','created_by','created_at','updated_by','updated_at'];
    protected $hidden = ['password','visible_password','remember_token'];
}
