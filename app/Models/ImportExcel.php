<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportExcel extends Model
{
    use HasFactory;
    protected $fillable = [
        'id', 'vendor_id', 'file', 'status'
    ];
}

