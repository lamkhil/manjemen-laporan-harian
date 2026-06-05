<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Report extends Model
{
    protected $fillable = [
        'user_id',
        'bidang_id',
        'category_id',
        'title',
        'subtitle',
        'report_date',
        'time_start',
        'time_end',
        'notes',
        'violations_count',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date:Y-m-d',
            'violations_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function bidang(): BelongsTo
    {
        return $this->belongsTo(Bidang::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReportItem::class)->orderBy('sort_order')->orderBy('time');
    }
}
