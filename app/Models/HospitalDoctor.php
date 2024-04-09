<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HospitalDoctor extends Model
{
    use HasFactory;
    protected $fillable = [
        'hospital_id', 'doctor_id'
    ];
}
