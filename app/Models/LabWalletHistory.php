<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabWalletHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        'lab_id', 'type', 'message','amount','created_at','updated_at'
    ];
}
