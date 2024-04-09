<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorLanguage extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'id', 'doctor_id', 'language_id'
    ];
}
