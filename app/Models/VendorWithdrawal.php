<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorWithdrawal extends Model
{
    use HasFactory;
    protected $fillable = [
        'id', 'vendor_id', 'amount','reference_proof','reference_no','status','existing_wallet','wallet'
    ];
}
