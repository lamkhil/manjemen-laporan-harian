<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['Kegiatan Lainnya',          '#3b82f6', false],
            ['Penertiban Pedagang Liar',  '#10b981', true ],
            ['Pengawasan Traffic Light',  '#f59e0b', false],
            ['Pengawasan TPS',            '#8b5cf6', false],
            ['Pengawasan Pedestrian & Bahu Jalan', '#ec4899', false],
            ['Baliho/Banner Liar',        '#dc2626', true ],
            ['Pengamanan Acara',          '#06b6d4', false],
            ['Penertiban PKL',            '#84cc16', true ],
        ];

        foreach ($rows as $i => [$name, $color, $isViolation]) {
            Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'color' => $color,
                    'is_violation' => $isViolation,
                    'is_active' => true,
                    'sort_order' => $i + 1,
                ]
            );
        }
    }
}
