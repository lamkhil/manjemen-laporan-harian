<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $bidangs = DB::table('bidangs')->pluck('name', 'id');

        DB::table('reports')->orderBy('id')->chunkById(200, function ($reports) use ($bidangs) {
            foreach ($reports as $r) {
                $title = $r->bidang_id && isset($bidangs[$r->bidang_id])
                    ? 'LAPORAN HARIAN ' . mb_strtoupper($bidangs[$r->bidang_id])
                    : 'LAPORAN HARIAN DPMPTSP';
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
