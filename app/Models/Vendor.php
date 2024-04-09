<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;
    protected $fillable = [
        'id', 'owner_name','store_name','phone_number','email','password','profile_picture','status','admin_user_id','store_image','address','latittude','longitude','static_map','manual_address','opening_time','closing_time','overall_ratings','no_of_ratings','document_update_status','document_approved_status','timing_update_status','address_update_status','phone_with_code','online_status','order_status','order_id','wallet','hospital_id','is_recommended', 'order_commission'
    ];
}
