<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;
    protected $fillable = [
        'id', 'customer_name', 'phone_number','email','password','profile_picture','wallet','status','fcm_token','phone_with_code','last_active_address','pre_existing_desease', 'blood_group', 'gender','overall_ratings','no_of_ratings'
    ];
}
