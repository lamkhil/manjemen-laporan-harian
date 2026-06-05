<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['report_date', 'shift']);
        });
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('shift');
            $table->index('report_date');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['report_date']);
            $table->string('shift', 32)->default('Pagi')->after('report_date');
            $table->index(['report_date', 'shift']);
        });
    }
};
