<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultationRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'id','date','time','consultation_type','patient_id','doctor_id','status', 'created_at','updated_at','total','payment_mode', 'rating','customer_rating', 'comments', 'customer_comments'
    ];
}
