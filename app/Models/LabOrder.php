<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabOrder extends Model
{
    use HasFactory;
    protected $fillable = [
        'customer_id','package_id','lab_id', 'address_id', 'total','sub_total','promo_id','discount','tax','collective_person','payment_mode','special_instruction','status','created_at','updated_at','items','patient_name','patient_dob','patient_gender','booking_type'
    ];
}
