<?php

namespace Database\Seeders;

use App\Models\Bidang;
use App\Models\JenisLayanan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BidangSeeder extends Seeder
{
    public function run(): void
    {
        $bidangs = [
            ['Sekretariat', 'Bidang sekretariat'],
            ['Penanaman Modal', 'Bidang Penanaman Modal (Klinik Investasi)'],
            ['PTSP Perizinan Berusaha', 'PTSP perizinan berusaha (NIB, KBLI, OSS RBA)'],
            ['PTSP Perizinan Non Berusaha', 'PTSP perizinan non berusaha (IMB, reklame, dll.)'],
            ['UPTSA Pusat', 'UPTSA Pusat (Siola)'],
            ['UPTSA Menur', 'UPTSA Menur'],
            ['UPTSA Pakal', 'UPTSA Pakal'],
            ['UPTSA Nambangan', 'UPTSA Nambangan'],
        ];

        $bidangModels = [];
        foreach ($bidangs as $i => [$name, $desc]) {
            $bidangModels[$name] = Bidang::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'description' => $desc, 'sort_order' => $i + 1, 'is_active' => true]
            );
        }

        // Jenis layanan per bidang
        $jl = [
            'Penanaman Modal' => [
                'Konsultasi Investasi', 'Konsultasi LKPM', 'Verifikasi Berkas SSWALFA',
                'Konsultasi Insentif', 'Pendampingan OSS',
            ],
            'PTSP Perizinan Berusaha' => [
                'Konsultasi OSS', 'Penerbitan NIB', 'Penambahan KBLI',
                'Konsultasi Penerbitan KBLI', 'Konsultasi Migrasi Berbasis Resiko',
                'Pembuatan Akun OSS', 'Perubahan Data Usaha',
            ],
            'PTSP Perizinan Non Berusaha' => [
                'IMB / PBG', 'Izin Reklame', 'Izin Pemakaman',
                'Izin Pemakaian Tanah', 'Izin Trayek',
            ],
            'UPTSA Pusat' => [
                'Loket Pengambilan', 'Loket CS', 'Loket Dinkoperdag',
                'Loket BPKAD', 'Loket Dishub', 'Loket Disbudporapar',
                'Loket Dispendik', 'Loket Dinkes', 'Loket Pengaduan',
                'Loket Informasi', 'Loket Lansia', 'Loket Mandiri',
                'Loket Reklame', 'Pengisian SKM',
            ],
            'UPTSA Menur' => ['Pelayanan Umum', 'Loket Perizinan', 'Loket Informasi'],
            'UPTSA Pakal' => ['Pelayanan Umum', 'Loket Perizinan', 'Loket Informasi'],
            'UPTSA Nambangan' => ['Pelayanan Umum', 'Loket Perizinan', 'Loket Informasi'],
            'Sekretariat' => ['Surat Masuk', 'Surat Keluar', 'Disposisi', 'Rapat / Sosialisasi'],
        ];

        foreach ($jl as $bidangName => $items) {
            $bid = $bidangModels[$bidangName] ?? null;
            if (! $bid) continue;
            foreach ($items as $i => $name) {
                JenisLayanan::updateOrCreate(
                    ['bidang_id' => $bid->id, 'name' => $name],
                    ['is_active' => true, 'sort_order' => $i + 1]
                );
            }
        }
    }
}
