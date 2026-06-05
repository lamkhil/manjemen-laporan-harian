<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'nip',
        'email',
        'password',
        'role',
        'unit_kerja',
        'jabatan',
        'bidang_id',
        'default_lokasi_id',
        'default_loket_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function bidang(): BelongsTo
    {
        return $this->belongsTo(Bidang::class);
    }

    public function defaultLokasi(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class, 'default_lokasi_id');
    }

    public function defaultLoket(): BelongsTo
    {
        return $this->belongsTo(Loket::class, 'default_loket_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
