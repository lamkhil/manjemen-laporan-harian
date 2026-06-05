<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $bidangs = DB::table('bidangs')->pluck('name', 'id');

        $months = [
            1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL',
            5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS',
            9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER',
        ];

        DB::table('reports')->orderBy('id')->chunkById(200, function ($reports) use ($bidangs, $months) {
            foreach ($reports as $r) {
                $bidangName = $r->bidang_id && isset($bidangs[$r->bidang_id]) ? $bidangs[$r->bidang_id] : 'DPMPTSP';
                $base = 'LAPORAN HARIAN ' . mb_strtoupper($bidangName);
                try {
                    $dt = Carbon::parse($r->report_date);
                    $title = $base . ' ' . $dt->day . ' ' . $months[(int) $dt->month] . ' ' . $dt->year;
                } catch (\Throwable $e) {
                    $title = $base;
                }
                if ($r->title !== $title) {
                    DB::table('reports')->where('id', $r->id)->update(['title' => $title]);
                }
            }
        });
    }

    public function down(): void
    {
        // No-op: previous titles are not preserved.
    }
};
