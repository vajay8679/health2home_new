<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    use HasFactory;
    public function getSymptomAttribute($value)
    {
        return explode(',', $value);
    }

    public function setSymptomAttribute($value)
    {
        $this->attributes['symptom'] = implode(',', $value);
    }
    
   protected $fillable = [
        'id', 'doctor_name', 'qualification','profile_image','phone_number','gender','email','password','username','experience','specialist','providing_service','overall_rating','otp','document_status','profile_status','calender_status','wallet','earnings','status','blood_group','fcm_token','profile_status','document_update_status','phone_with_code','c_id','c_stat','unique_code','hospital_id','additional_qualification','sub_specialist','no_of_ratings','overall_ratings','direct_appointment_commssion', 'online_booking_commssion','is_recommended'
    ];
}
