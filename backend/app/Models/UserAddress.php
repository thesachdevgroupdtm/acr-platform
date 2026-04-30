<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    use HasFactory;

    protected $table = 'user_addresses';
    protected $fields = ['user_id', 'address', 'zip', 'city'];

    public function userDetail()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
