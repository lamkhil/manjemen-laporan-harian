<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_items', function (Blueprint $table) {
            $table->string('location')->nullable()->change();

            $table->foreignId('lokasi_id')->nullable()->after('category_id')->constrained('lokasis')->nullOnDelete();
            $table->foreignId('loket_id')->nullable()->after('lokasi_id')->constrained('lokets')->nullOnDelete();

            $table->string('nib', 64)->nullable()->after('loket_id');
            $table->string('applicant_name')->nullable()->after('nib');
            $table->string('gender', 4)->nullable()->after('applicant_name');
            $table->string('company')->nullable()->after('gender');
            $table->text('company_address')->nullable()->after('company');
            $table->string('phone', 64)->nullable()->after('company_address');
            $table->string('email')->nullable()->after('phone');
            $table->string('purpose')->nullable()->after('email');
            $table->text('complaint')->nullable()->after('purpose');
            $table->text('solution')->nullable()->after('complaint');
            $table->string('signature_path')->nullable()->after('solution');

            $table->index('lokasi_id');
            $table->index('loket_id');
        });
    }

    public function down(): void
    {
        Schema::table('report_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lokasi_id');
            $table->dropConstrainedForeignId('loket_id');
            $table->dropColumn([
                'nib', 'applicant_name', 'gender', 'company', 'company_address',
                'phone', 'email', 'purpose', 'complaint', 'solution', 'signature_path',
            ]);
            $table->string('location')->nullable(false)->change();
        });
    }
};
