<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\HasUuid;

class Wishlist extends Model
{
    use HasUuid;
    protected $fillable = ['user_id', 'product_id'];

    public function user()
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
