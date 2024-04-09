<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HospitalPatientHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        'hospital_patient_id', 'date','purpose_of_visit'
    ];
}
