<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->default('LAPORAN PENGAWASAN KEWILAYAHAN');
            $table->string('subtitle')->nullable();
            $table->string('kecamatan')->nullable();
            $table->date('report_date');
            $table->string('shift', 32)->default('Pagi');
            $table->time('time_start')->nullable();
            $table->time('time_end')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamps();

            $table->index(['report_date', 'shift']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
