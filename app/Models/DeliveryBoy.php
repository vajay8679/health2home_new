<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryBoy extends Model
{
    use HasFactory;
     protected $fillable = [
        'id', 'delivery_boy_name', 'phone_number','email','password','profile_picture','online_status','status','otp','order_status','order_id'
    ];
}
