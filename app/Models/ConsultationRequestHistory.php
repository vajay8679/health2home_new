<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultationRequestHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        'cr_id', 'created_at', 'updated_at'
    ];
}
