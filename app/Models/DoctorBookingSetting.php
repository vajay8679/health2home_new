<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorBookingSetting extends Model
{
    use HasFactory;
    protected $fillable = [
        'id', 'doctor_id','direct_appointment_fee','direct_appointment_time','direct_appointment_status','online_booking_status','online_booking_fee','online_booking_time','created_at','updated_at'
    ];
}
