<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (! Schema::hasColumn('users', 'nip')) {
            DB::statement('ALTER TABLE users ADD COLUMN nip varchar(32) NULL');
            DB::statement('CREATE UNIQUE INDEX users_nip_unique ON users(nip)');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'nip')) {
            DB::statement('DROP INDEX IF EXISTS users_nip_unique');
            DB::statement('ALTER TABLE users DROP COLUMN nip');
        }
    }
};
