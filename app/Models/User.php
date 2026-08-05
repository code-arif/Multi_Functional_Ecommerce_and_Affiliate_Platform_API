<?php

namespace App\Models;


use App\Models\Role;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Cart\Models\Cart;
use Modules\Cart\Models\Wishlist;
use Modules\Core\Traits\HasUuid;
use Modules\Orders\Models\Order;
use Modules\Reviews\Models\Review;
use Modules\Support\Models\ChatRoom;
use Modules\Vendor\Models\Vendor;
use Modules\Vendor\Models\VendorStaff;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasUuid;
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, MustVerifyEmailTrait, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'avatar',
        'status',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relationships

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function defaultAddress(): HasOne
    {
        return $this->hasOne(Address::class)->where('is_default', true);
    }

    // public function orders(): HasMany
    // {
    //     return $this->hasMany(Order::class);
    // }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function chatRooms(): HasMany
    {
        return $this->hasMany(ChatRoom::class);
    }

    // Vendor module relationships
    public function vendor(): HasOne
    {
        return $this->hasOne(Vendor::class);
    }

    public function vendorStaff(): HasMany
    {
        return $this->hasMany(VendorStaff::class);
    }

    // Role Helpers (bridge from Spatie)
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_users');
    }
    public function isAdmin(): bool
    {
        return $this->hasRole(['super-admin', 'admin']);
    }

    public function isModerator(): bool
    {
        return $this->hasRole('moderator');
    }

    public function isVendor(): bool
    {
        return $this->hasRole('vendor');
    }

    // Accessors
    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) return null;
        return str_starts_with($this->avatar, 'http')
            ? $this->avatar
            : asset('storage/' . $this->avatar);
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }
}
