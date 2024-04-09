<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hospital extends Model
{
    use HasFactory;
    protected $fillable = [
        'id', 'admin_user_id', 'hospital_name','hospital_logo','phone_number', 'phone_with_code', 'email','password','username','address', 'latitude', 'longitude','opening_time','closing_time', 'description', 'overall_ratings','no_of_ratings','status','created_at','updated_at','wallet','website','type','is_recommended'
    ];
}
