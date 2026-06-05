<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (! Schema::hasColumn('reports', 'category_id')) {
                $table->foreignId('category_id')->nullable()->after('user_id')->constrained('categories')->nullOnDelete();
                $table->index('category_id');
            }
        });

        // backfill default category for old rows: use first service category, fallback to first category
        $defaultCatId = DB::table('categories')->where('is_service', true)->orderBy('id')->value('id')
            ?? DB::table('categories')->orderBy('id')->value('id');
        if ($defaultCatId) {
            DB::table('reports')->whereNull('category_id')->update(['category_id' => $defaultCatId]);
        }

        Schema::table('reports', function (Blueprint $table) {
            if (Schema::hasColumn('reports', 'kecamatan')) {
                $table->dropColumn('kecamatan');
            }
        });

        DB::statement("ALTER TABLE reports ALTER COLUMN title SET DEFAULT 'LAPORAN HARIAN DPMPTSP'");
        DB::table('reports')->where('title', 'LAPORAN PENGAWASAN KEWILAYAHAN')->update(['title' => 'LAPORAN HARIAN DPMPTSP']);
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('kecamatan')->nullable();
        });

        DB::statement("ALTER TABLE reports ALTER COLUMN title SET DEFAULT 'LAPORAN PENGAWASAN KEWILAYAHAN'");

        Schema::table('reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
