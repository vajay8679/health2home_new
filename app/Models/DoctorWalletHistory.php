<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorWalletHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        'id', 'doctor_id', 'type','message','amount','created_at','updated_at'
    ];
}
