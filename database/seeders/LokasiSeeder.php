<?php

namespace Database\Seeders;

use App\Models\Lokasi;
use App\Models\Loket;
use Illuminate\Database\Seeder;

class LokasiSeeder extends Seeder
{
    public function run(): void
    {
        $klinik = Lokasi::updateOrCreate(
            ['name' => 'Klinik Investasi'],
            ['description' => 'Layanan konsultasi & perizinan DPMPTSP', 'sort_order' => 1, 'is_active' => true]
        );
        $mpp = Lokasi::updateOrCreate(
            ['name' => 'Mall Pelayanan Publik'],
            ['description' => 'MPP Siola', 'sort_order' => 2, 'is_active' => true]
        );
        Lokasi::updateOrCreate(
            ['name' => 'Kantor DPMPTSP'],
            ['description' => 'Kantor pusat DPMPTSP Kota Surabaya', 'sort_order' => 3, 'is_active' => true]
        );

        $loketRows = [
            ['Loket Perizinan',  $klinik->id],
            ['Loket Pengaduan',  $klinik->id],
            ['Loket Informasi',  $klinik->id],
            ['Loket Perizinan',  $mpp->id],
            ['Loket Pengaduan',  $mpp->id],
        ];

        foreach ($loketRows as $i => [$name, $lokasiId]) {
            Loket::updateOrCreate(
                ['name' => $name, 'lokasi_id' => $lokasiId],
                ['is_active' => true, 'sort_order' => $i + 1]
            );
        }
    }
}
