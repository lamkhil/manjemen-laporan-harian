<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportItem extends Model
{
    protected $fillable = [
        'report_id',
        'category_id',
        'lokasi_id',
        'loket_id',
        'jenis_layanan_id',
        'time',
        'location',
        'notes',
        'sort_order',
        'nib',
        'applicant_name',
        'gender',
        'company',
        'company_address',
        'phone',
        'email',
        'purpose',
        'complaint',
        'solution',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class);
    }

    public function loket(): BelongsTo
    {
        return $this->belongsTo(Loket::class);
    }

    public function jenisLayanan(): BelongsTo
    {
        return $this->belongsTo(JenisLayanan::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ReportItemPhoto::class)->orderBy('sort_order');
    }
}
