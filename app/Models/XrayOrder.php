<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class XrayOrder extends Model
{
    use HasFactory;
    protected $fillable = [
        'customer_id','lab_id', 'address_id', 'status','created_at','updated_at','items','patient_name','patient_age','patient_gender','appointment_date','appointment_time'
    ];
}
