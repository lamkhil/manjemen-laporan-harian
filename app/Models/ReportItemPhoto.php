<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ReportItemPhoto extends Model
{
    protected $fillable = [
        'report_item_id',
        'path',
        'original_name',
        'mime',
        'size',
        'sort_order',
    ];

    protected $appends = ['url'];

    public function reportItem(): BelongsTo
    {
        return $this->belongsTo(ReportItem::class);
    }

    public function getUrlAttribute(): ?string
    {
        return $this->path ? Storage::disk('public')->url($this->path) : null;
    }
}
