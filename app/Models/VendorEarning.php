<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorEarning extends Model
{
    use HasFactory;
    protected $fillable = [
        'id', 'order_id', 'vendor_id','amount','created_at','updated_at'
    ];
}
