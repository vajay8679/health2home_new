<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabOrderItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'id', 'order_id', 'item_id','item_name','price'
    ];
}
