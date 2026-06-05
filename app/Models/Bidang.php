<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Bidang extends Model
{
    protected $table = 'bidangs';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Bidang $b) {
            if (empty($b->slug)) {
                $b->slug = Str::slug($b->name);
            }
        });
    }

    public function jenisLayanans(): HasMany
    {
        return $this->hasMany(JenisLayanan::class)->orderBy('sort_order')->orderBy('name');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}
