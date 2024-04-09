<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorDocument extends Model
{
    use HasFactory;
    protected $fillable = [
        'id', 'doctor_id','document_name','document_path','status'
    ];
}
