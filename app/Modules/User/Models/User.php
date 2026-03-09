<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Modules\Address\Models\Address;
use App\Modules\Order\Models\Order;
use App\Modules\Favorite\Models\Favorite;
use App\Modules\Review\Models\Review;
use App\Modules\Notification\Models\Notification;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'email',
        'password',
        'user_type',
        'name',
        'phone',
        'cpf',
        'birth_date',
        'profile_picture_path',
        'is_active',
        'email_verified_at',
        'last_login'
    ];

    protected $hidden = [
        'password',
        'api_token',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime',
        'last_login' => 'datetime'
    ];

    // Relacionamentos
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function defaultAddress()
    {
        return $this->hasOne(Address::class)->where('is_default', true);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'client_id');
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function unreadNotifications()
    {
        return $this->hasMany(Notification::class)->where('is_read', false);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeClients($query)
    {
        return $query->where('user_type', 'client');
    }

    public function scopeAdmins($query)
    {
        return $query->whereIn('user_type', ['admin', 'manager']);
    }

    // Mutators
    public function setPasswordHashAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }

    // Accessors
    public function getIsAdminAttribute()
    {
        return in_array($this->user_type, ['admin', 'manager']);
    }

    public function getFullNameAttribute()
    {
        return $this->name;
    }

    // Métodos auxiliares
    public function hasRole($role)
    {
        return $this->user_type === $role;
    }

    public function canManage()
    {
        return in_array($this->user_type, ['admin', 'manager']);
    }
}

