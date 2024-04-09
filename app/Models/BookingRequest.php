<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'id', 'patient_id','doctor_id','date','time','start_time','title','description','status', 'created_at','updated_at','total_amount','payment_mode','rating','comments','customer_rating','customer_comments'
    ];
}
