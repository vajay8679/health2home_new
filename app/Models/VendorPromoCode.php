<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorPromoCode extends Model
{
    use HasFactory;
    protected $fillable = [
        'id', 'status', 'customer_id','vendor_id','promo_name','promo_code','description','long_description','promo_type','discount','min_purchase_price','max_discount_value','redemptions'
    ];
}
