<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('report_items', 'signature_path')) {
            $paths = DB::table('report_items')->whereNotNull('signature_path')->pluck('signature_path');
            foreach ($paths as $p) {
                Storage::disk('public')->delete($p);
            }
            Schema::table('report_items', function (Blueprint $table) {
                $table->dropColumn('signature_path');
            });
        }
    }

    public function down(): void
    {
        Schema::table('report_items', function (Blueprint $table) {
            $table->string('signature_path')->nullable()->after('solution');
        });
    }
};
