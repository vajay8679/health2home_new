<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPrescriptionItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'id', 'customer_prescription_id', 'medicine_name','morning','afternoon', 'evening','night', 'status'
    ];
}
