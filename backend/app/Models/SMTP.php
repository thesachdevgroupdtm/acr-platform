<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SMTP extends Model
{
    use HasFactory;
    protected $table = 'smtp';
    protected $fillable = ['sender_name', 'mail_address', 'mail_mailer', 'mail_username', 'mail_host', 'mail_password', 'mail_port', 'mail_enc'];
}
