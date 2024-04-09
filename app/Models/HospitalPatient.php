<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HospitalPatient extends Model
{
    use HasFactory;
    protected $fillable = [
        'hospital_id', 'patient_name','phone_number'
    ];
}
