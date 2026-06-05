<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // [name, color, is_violation, is_service]
        $rows = [
            ['Pelayanan',                          '#0ea5e9', false, true ],
            ['Pengaduan',                          '#f97316', false, true ],
            ['Umum',                               '#64748b', false, false],
            ['Pengawasan Pedestrian & Bahu Jalan', '#ec4899', false, false],
            ['Kegiatan Lainnya',                   '#3b82f6', false, false],
            ['Penertiban Pedagang Liar',           '#10b981', true,  false],
            ['Pengawasan Traffic Light',           '#f59e0b', false, false],
            ['Pengawasan TPS',                     '#8b5cf6', false, false],
            ['Baliho/Banner Liar',                 '#dc2626', true,  false],
            ['Pengamanan Acara',                   '#06b6d4', false, false],
            ['Penertiban PKL',                     '#84cc16', true,  false],
        ];

        foreach ($rows as $i => [$name, $color, $isViolation, $isService]) {
            Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'color' => $color,
                    'is_violation' => $isViolation,
                    'is_service' => $isService,
                    'is_active' => true,
                    'sort_order' => $i + 1,
                ]
            );
        }
    }
}
