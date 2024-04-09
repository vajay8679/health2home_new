<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorDocument extends Model
{
    use HasFactory;
    protected $fillable = [
        'id', 'vendor_id','document_name','document_path','status'
    ];
}
