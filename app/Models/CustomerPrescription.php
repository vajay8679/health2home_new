<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPrescription extends Model
{
    use HasFactory;
    protected $fillable = [
        'id','booking_id','patient_id','subjective_information','objective_information','assessment','plan','doctor_notes','doctor_id','date'
    ];
}
