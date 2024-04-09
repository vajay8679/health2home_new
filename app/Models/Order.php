<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
    
    protected $fillable = [
        'customer_id','prescription_id','vendor_id', 'address_id', 'total','discount','tax','sub_total','promo_id','delivered_by','delivery_charge','payment_mode','status','rating','items','vendor_percent','created_at','updated_at','comments','prescription'
    ];
}
