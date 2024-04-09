<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Laboratory extends Model
{
    use HasFactory;
    protected $fillable = [
        'id', 'lab_name', 'phone_number','email','password','lab_image','wallet','status','fcm_token','phone_with_code', 'admin_user_id','is_recommended','lab_commission'

        ];
}
