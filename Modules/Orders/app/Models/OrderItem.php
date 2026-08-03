<?php

namespace Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Orders\Database\Factories\OrderItemFactory;

class OrderItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): OrderItemFactory
    // {
    //     // return OrderItemFactory::new();
    // }
}
