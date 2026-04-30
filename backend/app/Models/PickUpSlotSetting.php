<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PickUpSlotSetting extends Model
{
    use HasFactory;
    protected $table = 'pick_up_slot_settings';
    protected $fields = ['is_afternoon', 'time', 'created_by', 'updated_by'];
}
