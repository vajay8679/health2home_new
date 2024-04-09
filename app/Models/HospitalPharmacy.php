<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HospitalPharmacy extends Model
{
    use HasFactory;
    protected $fillable = [
        'hospital_id', 'pharmacy_id'
    ];
}
