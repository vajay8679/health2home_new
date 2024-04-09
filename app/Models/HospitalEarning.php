<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HospitalEarning extends Model
{
    use HasFactory;
    protected $fillable = [
        'id','hospital_id', 'type', 'ref_id','source_id','amount','created_at','updated_at'
    ];
}
