<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@dpmptsp-surabaya.my.id'],
            [
                'name' => 'Administrator',
                'nip' => '198001012005011001',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'unit_kerja' => 'DPMPTSP Kota Surabaya',
                'jabatan' => 'Administrator Sistem',
            ]
        );

        User::updateOrCreate(
            ['email' => 'user@dpmptsp-surabaya.my.id'],
            [
                'name' => 'Petugas Sukolilo',
                'nip' => '198505152010012003',
                'password' => Hash::make('user123'),
                'role' => 'user',
                'unit_kerja' => 'Kecamatan Sukolilo',
                'jabatan' => 'Petugas Pengawas Wilayah',
            ]
        );

        $this->call(CategorySeeder::class);
    }
}
