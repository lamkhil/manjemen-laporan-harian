<?php

namespace Database\Seeders;

use App\Models\Bidang;
use App\Models\Category;
use Illuminate\Support\Facades\Hash;
use App\Models\JenisLayanan;
use App\Models\Lokasi;
use App\Models\Loket;
use App\Models\Report;
use App\Models\ReportItem;
use App\Models\ReportItemPhoto;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class DemoPelayananSeeder extends Seeder
{
    public function run(): void
    {
        $klinik = Lokasi::where('name', 'Klinik Investasi')->first();
        $loketPerizinan = Loket::where('name', 'Loket Perizinan')->where('lokasi_id', $klinik?->id)->first();
        $bidangPM = Bidang::where('slug', 'penanaman-modal')->first();
        $bidangPTSPB = Bidang::where('slug', 'ptsp-perizinan-berusaha')->first();

        $petugasNames = ['Helmi', 'Shofi', 'Candra', 'Rio', 'Neno', 'Miftah'];
        $petugas = [];
        foreach ($petugasNames as $i => $name) {
            $petugas[$name] = User::updateOrCreate(
                ['nip' => 'PTG' . str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT)],
                [
                    'name' => $name,
                    'email' => strtolower($name) . '@dpmptsp-surabaya.my.id',
                    'password' => Hash::make('petugas123'),
                    'role' => 'user',
                    'unit_kerja' => 'DPMPTSP Kota Surabaya',
                    'jabatan' => 'Petugas Klinik Investasi',
                    'bidang_id' => $bidangPM?->id,
                    'default_lokasi_id' => $klinik?->id,
                    'default_loket_id' => $loketPerizinan?->id,
                ]
            );
        }

        $catPelayanan = Category::where('slug', 'pelayanan')->first();
        if (! $catPelayanan || ! $klinik) {
            return;
        }

        $owner = User::where('role', 'admin')->first() ?? User::first();
        $reportDate = Carbon::parse('2026-05-20');

        $report = Report::updateOrCreate(
            ['user_id' => $owner->id, 'report_date' => $reportDate->toDateString(), 'title' => 'LAPORAN PELAYANAN KLINIK INVESTASI'],
            [
                'category_id' => $catPelayanan->id,
                'bidang_id' => $bidangPM?->id,
                'subtitle' => 'Demo data pelayanan',
                'time_start' => '08:00',
                'time_end' => '15:00',
                'notes' => 'Data demo hasil seeding.',
                'status' => 'draft',
            ]
        );

        $jlByName = JenisLayanan::whereIn('bidang_id', array_filter([$bidangPM?->id, $bidangPTSPB?->id]))->get()->keyBy(fn ($j) => strtolower($j->name));
        $resolveJL = function (string $complaint) use ($jlByName) {
            $c = strtolower($complaint);
            if (str_contains($c, 'penambahan kbli')) return $jlByName['penambahan kbli'] ?? null;
            if (str_contains($c, 'penerbitan kbli')) return $jlByName['konsultasi penerbitan kbli'] ?? null;
            if (str_contains($c, 'migrasi')) return $jlByName['konsultasi migrasi berbasis resiko'] ?? null;
            if (str_contains($c, 'penerbitan nib')) return $jlByName['penerbitan nib'] ?? null;
            if (str_contains($c, 'lkpm')) return $jlByName['konsultasi lkpm'] ?? null;
            if (str_contains($c, 'oss')) return $jlByName['konsultasi oss'] ?? null;
            return $jlByName['konsultasi oss'] ?? null;
        };

        // truncate existing items for idempotent demo
        $report->items()->where('category_id', $catPelayanan->id)->delete();

        $rows = [
            ['nib' => '0000000000000', 'name' => 'RIRIN',               'gender' => 'L', 'company' => 'PERORANGAN',                              'address' => '-',                                                                                                          'purpose' => 'PERIZINAN', 'complaint' => 'KONSULTASI OSS',                  'solution' => 'Tutorial', 'petugas' => 'Helmi'],
            ['nib' => '0000000000000', 'name' => 'Farhan',              'gender' => 'L', 'company' => 'masyarakat pra usaha',                    'address' => '-',                                                                                                          'purpose' => 'PERIZINAN', 'complaint' => 'konsultasi oss',                  'solution' => 'Tutorial', 'petugas' => 'Shofi'],
            ['nib' => '0000000000000', 'name' => 'Fadli',               'gender' => 'L', 'company' => 'masyarakat pra usaha',                    'address' => '-',                                                                                                          'purpose' => 'PERIZINAN', 'complaint' => 'konsultasi oss',                  'solution' => 'Tutorial', 'petugas' => 'Candra'],
            ['nib' => '0000000000000', 'name' => 'Rahayu',              'gender' => 'L', 'company' => 'CV Mitra Mandiri',                        'address' => '-',                                                                                                          'purpose' => 'PERIZINAN', 'complaint' => 'konsultasi oss',                  'solution' => 'Tutorial', 'petugas' => 'Rio'],
            ['nib' => '9120302302118', 'name' => 'TONI',                'gender' => 'L', 'company' => 'PT SEDOSO INGGIL PESONA KREASI',           'address' => 'JL ADITYAWARMAN NO 52 SAMPAI 54A WONOKROMO SURABAYA',                                                         'purpose' => 'PERIZINAN', 'complaint' => 'PENAMBAHAN KBLI',                 'solution' => 'Tutorial', 'petugas' => 'Candra'],
            ['nib' => '9120005712851', 'name' => 'GEMA AGUNG ASTIKO',   'gender' => 'L', 'company' => 'PT SARANA LOGISTIK INDONESIA',             'address' => 'JL WR SUPRATMAN NO 2 TEGALSARI SURABAYA',                                                                    'purpose' => 'PERIZINAN', 'complaint' => 'KONSULTASI PENERBITAN KBLI',     'solution' => 'Tutorial', 'petugas' => 'Candra'],
            ['nib' => '1218000320723', 'name' => 'TEGAR PERKASA',       'gender' => 'L', 'company' => 'PT TEGES TRENGGINAS PRAKOSO',              'address' => 'JL GAYUNGSARI BARAT 12 GA 7 SURABAYA',                                                                       'purpose' => 'PERIZINAN', 'complaint' => 'KONSULTASI MIGRASI KE BERBASIS RESIKO', 'solution' => 'Tutorial', 'petugas' => 'Candra'],
            ['nib' => '2005260025414', 'name' => 'NOVIE SETIYANINGSIH', 'gender' => 'P', 'company' => 'PO NOVIE SETIYANINGSIH',                   'address' => 'DUPAK JAYA VI NO 48 BUBUTAN SURABAYA',                                                                       'purpose' => 'PERIZINAN', 'complaint' => 'PEMBUATAN AKUN DAN PENERBITAN NIB', 'solution' => 'Tutorial', 'petugas' => 'Candra'],
            ['nib' => '2005260018405', 'name' => 'WINARSIH',            'gender' => 'P', 'company' => 'PERORANGAN',                              'address' => 'SURABAYAN 3/32-C, Kel. Kedungdoro, Kec. Tegalsari, Kota Surabaya',                                            'purpose' => 'PERIZINAN', 'complaint' => 'konsultasi oss',                  'solution' => 'Tutorial', 'petugas' => 'Neno'],
            ['nib' => '2005260018405', 'name' => 'H. MOHAMMAD HUSNAN NAWAWI', 'gender' => 'L', 'company' => 'PERORANGAN',                         'address' => 'NYAMPLUNGAN 11 / 7, Kel. Ampel, Kec. Semampir, Kota Surabaya',                                               'purpose' => 'PERIZINAN', 'complaint' => 'konsultasi oss',                  'solution' => 'Tutorial', 'petugas' => 'Miftah'],
        ];

        foreach ($rows as $i => $r) {
            $jl = $resolveJL($r['complaint']);
            $item = ReportItem::create([
                'report_id' => $report->id,
                'category_id' => $catPelayanan->id,
                'lokasi_id' => $klinik->id,
                'loket_id' => $loketPerizinan?->id,
                'jenis_layanan_id' => $jl?->id,
                'sort_order' => $i + 1,
                'time' => sprintf('%02d:%02d', 8 + intdiv($i, 2), ($i % 2) * 30),
                'location' => null,
                'nib' => $r['nib'],
                'applicant_name' => $r['name'],
                'gender' => $r['gender'],
                'company' => $r['company'],
                'company_address' => $r['address'],
                'purpose' => $r['purpose'],
                'complaint' => $r['complaint'],
                'solution' => $r['solution'],
                'notes' => 'Petugas: ' . $r['petugas'],
            ]);

            // skip if photos already attached (idempotency)
            if ($item->photos()->exists()) continue;

            // Generate one demo photo per item
            $path = $this->generateDemoPhoto($report->id, $item->id, $r['name'], $r['petugas']);
            if ($path) {
                ReportItemPhoto::create([
                    'report_item_id' => $item->id,
                    'path' => $path,
                    'original_name' => 'demo-' . $item->id . '.jpg',
                    'mime' => 'image/jpeg',
                    'size' => Storage::disk('public')->size($path),
                    'sort_order' => 1,
                ]);
            }
        }
    }

    protected function generateDemoPhoto(int $reportId, int $itemId, string $applicant, string $petugas): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }
        $w = 600;
        $h = 400;
        $im = imagecreatetruecolor($w, $h);

        // pick a palette based on item id
        $palettes = [
            ['#0ea5e9', '#082f49'],
            ['#10b981', '#064e3b'],
            ['#f59e0b', '#78350f'],
            ['#8b5cf6', '#4c1d95'],
            ['#ec4899', '#831843'],
            ['#06b6d4', '#164e63'],
        ];
        [$bgHex, $fgHex] = $palettes[$itemId % count($palettes)];
        $hex2rgb = fn ($h) => [hexdec(substr($h, 1, 2)), hexdec(substr($h, 3, 2)), hexdec(substr($h, 5, 2))];
        [$br, $bgG, $bgB] = $hex2rgb($bgHex);
        [$fgR, $fgG, $fgB] = $hex2rgb($fgHex);
        $bg = imagecolorallocate($im, $br, $bgG, $bgB);
        $fg = imagecolorallocate($im, $fgR, $fgG, $fgB);
        $white = imagecolorallocate($im, 255, 255, 255);
        imagefill($im, 0, 0, $bg);

        // decorative shapes
        imagefilledrectangle($im, 0, 0, $w, 60, $fg);
        imagefilledellipse($im, 60, 350, 120, 120, $fg);
        imagefilledellipse($im, 540, 80, 80, 80, $white);

        // text
        $label = 'DEMO PHOTO';
        $line1 = strtoupper($applicant);
        $line2 = 'Petugas: ' . $petugas;
        $line3 = 'Klinik Investasi - 20/05/2026';

        imagestring($im, 5, 20, 18, $label, $white);
        imagestring($im, 5, 20, 120, substr($line1, 0, 36), $white);
        imagestring($im, 3, 20, 160, $line2, $white);
        imagestring($im, 3, 20, 185, $line3, $white);

        $relPath = "reports/{$reportId}/demo-{$itemId}.jpg";
        $absPath = Storage::disk('public')->path($relPath);
        if (! is_dir(dirname($absPath))) {
            mkdir(dirname($absPath), 0755, true);
        }
        imagejpeg($im, $absPath, 78);
        imagedestroy($im);

        return $relPath;
    }
}
