<?php

namespace Modules\Vendor\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\HasUuid;

class VendorDocument extends Model
{
    use HasUuid;
    protected $fillable = [
        'vendor_id',
        'type',
        'document_path',
        'document_number',
        'expiry_date',
        'status',
        'rejection_reason',
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'verified_at' => 'datetime',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    public function getDocumentUrlAttribute(): string
    {
        return asset('storage/' . $this->document_path);
    }
}
