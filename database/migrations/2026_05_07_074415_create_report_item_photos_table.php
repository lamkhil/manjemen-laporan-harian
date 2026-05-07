<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_item_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_item_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime', 64)->nullable();
            $table->unsignedInteger('size')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('report_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_item_photos');
    }
};
