<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabEarning extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id','lab_id', 'address_id', 'amount','created_at','updated_at'
    ];
}
