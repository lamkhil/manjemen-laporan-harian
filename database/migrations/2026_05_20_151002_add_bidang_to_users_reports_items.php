<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('bidang_id')->nullable()->after('jabatan')->constrained('bidangs')->nullOnDelete();
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('bidang_id')->nullable()->after('user_id')->constrained('bidangs')->nullOnDelete();
            $table->index('bidang_id');
        });

        Schema::table('report_items', function (Blueprint $table) {
            $table->foreignId('jenis_layanan_id')->nullable()->after('loket_id')->constrained('jenis_layanans')->nullOnDelete();
            $table->index('jenis_layanan_id');
        });
    }

    public function down(): void
    {
        Schema::table('report_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('jenis_layanan_id');
        });
        Schema::table('reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bidang_id');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bidang_id');
        });
    }
};
