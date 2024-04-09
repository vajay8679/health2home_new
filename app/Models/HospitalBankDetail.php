<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HospitalBankDetail extends Model
{
    use HasFactory;
     protected $fillable = [
        'id', 'hospital_id', 'bank_name','bank_account_number','beneficiary_name','swift_code','status'
    ];
}
