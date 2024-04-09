<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HospitalWalletHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        'id', 'hospital_id', 'type','message','amount','created_at','updated_at'
    ];
}
