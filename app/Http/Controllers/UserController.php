<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $q = User::query()->with(['bidang:id,name', 'defaultLokasi:id,name', 'defaultLoket:id,name'])->orderBy('name');
        if ($s = $request->string('search')->toString()) {
            $q->where(fn ($w) => $w->where('name', 'ilike', "%$s%")
                ->orWhere('nip', 'ilike', "%$s%")
                ->orWhere('email', 'ilike', "%$s%"));
        }
        if ($r = $request->string('role')->toString()) {
            $q->where('role', $r);
        }

        return response()->json($q->paginate((int) $request->integer('per_page', 25)));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'nip' => 'required|string|max:32|unique:users,nip',
            'email' => 'nullable|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,user',
            'unit_kerja' => 'nullable|string|max:255',
            'jabatan' => 'nullable|string|max:255',
            'bidang_id' => 'nullable|exists:bidangs,id',
            'default_lokasi_id' => 'nullable|exists:lokasis,id',
            'default_loket_id' => 'nullable|exists:lokets,id',
        ]);

        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        return response()->json($user->load(['bidang:id,name', 'defaultLokasi:id,name', 'defaultLoket:id,name']), 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'nip' => 'sometimes|string|max:32|unique:users,nip,' . $user->id,
            'email' => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'role' => 'sometimes|in:admin,user',
            'unit_kerja' => 'nullable|string|max:255',
            'jabatan' => 'nullable|string|max:255',
            'bidang_id' => 'nullable|exists:bidangs,id',
            'default_lokasi_id' => 'nullable|exists:lokasis,id',
            'default_loket_id' => 'nullable|exists:lokets,id',
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json($user->fresh()->load(['bidang:id,name', 'defaultLokasi:id,name', 'defaultLoket:id,name']));
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorizeAdmin($request);
        abort_if($user->id === $request->user()->id, 422, 'Tidak bisa menghapus akun sendiri.');

        $user->delete();

        return response()->json(['message' => 'deleted']);
    }

    public function import(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');
        if (! $handle) {
            return response()->json(['message' => 'Gagal membaca file.'], 422);
        }

        $headers = null;
        $created = 0;
        $updated = 0;
        $errors = [];
        $row = 0;

        while (($cols = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $row++;

            if ($row === 1) {
                $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $cols);
                continue;
            }

            if (count(array_filter($cols, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue;
            }

            $data = [];
            foreach ($headers as $i => $h) {
                $data[$h] = isset($cols[$i]) ? trim((string) $cols[$i]) : null;
            }

            try {
                $nip = preg_replace('/\s+/', '', (string) ($data['nip'] ?? ''));
                $name = $data['nama'] ?? $data['name'] ?? null;
                $password = $data['password'] ?? null;
                $role = strtolower($data['role'] ?? 'user');
                $email = $data['email'] ?? null;

                if (! $nip || ! $name || ! $password) {
                    throw new \RuntimeException('NIP, Nama, dan Password wajib diisi.');
                }

                $payload = [
                    'name' => $name,
                    'email' => $email ?: null,
                    'role' => in_array($role, ['admin', 'user'], true) ? $role : 'user',
                    'unit_kerja' => $data['unit_kerja'] ?? $data['unit'] ?? null,
                    'jabatan' => $data['jabatan'] ?? null,
                    'password' => Hash::make($password),
                ];

                $existing = User::where('nip', $nip)->first();
                if ($existing) {
                    $existing->update($payload);
                    $updated++;
                } else {
                    User::create(array_merge($payload, ['nip' => $nip]));
                    $created++;
                }
            } catch (\Throwable $e) {
                $errors[] = "Baris $row: " . $e->getMessage();
            }
        }
        fclose($handle);

        return response()->json([
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
            'total_processed' => $created + $updated,
        ]);
    }

    public function exampleCsv(): StreamedResponse
    {
        $csv = "nip,nama,email,password,role,unit_kerja,jabatan\n"
            . "198001012005011001,Budi Hartono,budi@dpmptsp-surabaya.my.id,password123,admin,DPMPTSP Kota Surabaya,Kepala Bidang\n"
            . "198505152010012003,Siti Rahayu,siti@dpmptsp-surabaya.my.id,password123,user,DPMPTSP Kota Surabaya,Petugas Klinik Investasi\n"
            . "199003202012011004,Ahmad Yusuf,,password123,user,DPMPTSP Kota Surabaya,Petugas Loket Perizinan\n"
            . "3578012345678901,Dewi Lestari,dewi@dpmptsp-surabaya.my.id,password123,user,DPMPTSP Kota Surabaya,Pegawai Kontrak\n";

        return response()->streamDownload(
            fn () => print($csv),
            'contoh-import-pegawai.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    protected function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Hanya admin yang dapat mengelola pengguna.');
    }
}
