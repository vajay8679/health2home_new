<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HospitalLaboratory extends Model
{
    use HasFactory;
    protected $fillable = [
        'hospital_id', 'lab_id'
    ];
}
