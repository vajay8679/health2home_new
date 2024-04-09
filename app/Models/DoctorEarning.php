<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorEarning extends Model
{
    use HasFactory;
    protected $fillable = [
        'id', 'booking_id', 'doctor_id','amount','created_at','updated_at'
    ];
}
