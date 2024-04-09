<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'id', 'vendor_id', 'prescription_required','category_id','sub_category_id','brand_id','product_name','slug','image','marked_price','discount','description','price','unit_id','status'
    ];
}
