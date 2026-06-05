<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('nip', 'admin')->orWhere('email', 'admin@dpmptsp-surabaya.my.id')->first();
        if ($admin) {
            $admin->update([
                'name' => 'Administrator',
                'nip' => 'admin',
                'email' => 'admin@dpmptsp-surabaya.my.id',
                'password' => Hash::make('admin'),
                'role' => 'admin',
                'unit_kerja' => 'DPMPTSP Kota Surabaya',
                'jabatan' => 'Administrator Sistem',
            ]);
        } else {
            User::create([
                'name' => 'Administrator',
                'nip' => 'admin',
                'email' => 'admin@dpmptsp-surabaya.my.id',
                'password' => Hash::make('admin'),
                'role' => 'admin',
                'unit_kerja' => 'DPMPTSP Kota Surabaya',
                'jabatan' => 'Administrator Sistem',
            ]);
        }

        User::updateOrCreate(
            ['email' => 'user@dpmptsp-surabaya.my.id'],
            [
                'name' => 'Petugas DPMPTSP',
                'nip' => '198505152010012003',
                'password' => Hash::make('user123'),
                'role' => 'user',
                'unit_kerja' => 'DPMPTSP Kota Surabaya',
                'jabatan' => 'Petugas Klinik Investasi',
            ]
        );

        $this->call(BidangSeeder::class);
        $this->call(LokasiSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(DemoPelayananSeeder::class);
    }
}
