<?php

namespace App\Http\Controllers;

use App\Models\Bidang;
use App\Models\Report;
use App\Models\ReportItem;
use App\Models\ReportItemPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AktivitasController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $q = ReportItem::query()
            ->with([
                'category:id,name,color,is_violation,is_service',
                'lokasi:id,name',
                'loket:id,name',
                'jenisLayanan:id,name',
                'photos',
                'report:id,user_id,bidang_id,report_date,title,status,violations_count',
                'report.user:id,name',
                'report.bidang:id,name',
            ])
            ->orderByDesc(Report::select('report_date')->whereColumn('reports.id', 'report_items.report_id'))
            ->orderByDesc('report_items.id');

        $q->whereHas('report', function ($w) use ($request, $user) {
            if (! $user->isAdmin() && ! $request->boolean('all')) {
                $w->where('user_id', $user->id);
            }
            if ($from = $request->date('from')) {
                $w->whereDate('report_date', '>=', $from);
            }
            if ($to = $request->date('to')) {
                $w->whereDate('report_date', '<=', $to);
            }
            if ($date = $request->date('date')) {
                $w->whereDate('report_date', $date);
            }
            if ($bid = $request->integer('bidang_id')) {
                $w->where('bidang_id', $bid);
            }
        });

        if ($catId = $request->integer('category_id')) {
            $q->where('category_id', $catId);
        }
        if ($s = $request->string('search')->toString()) {
            $q->where(function ($w) use ($s) {
                $w->where('applicant_name', 'ilike', "%$s%")
                    ->orWhere('nib', 'ilike', "%$s%")
                    ->orWhere('company', 'ilike', "%$s%")
                    ->orWhere('complaint', 'ilike', "%$s%");
            });
        }

        $perPage = (int) $request->integer('per_page', 25);
        $perPage = max(1, min($perPage, 100));

        return response()->json($q->paginate($perPage));
    }

    public function show(Request $request, ReportItem $aktivita): JsonResponse
    {
        $this->authorizeAccess($request, $aktivita);

        $aktivita->load([
            'category', 'lokasi:id,name', 'loket:id,name', 'jenisLayanan:id,name', 'photos',
            'report:id,user_id,bidang_id,report_date,title,status,notes,time_start,time_end,violations_count',
            'report.user:id,name', 'report.bidang:id,name',
        ]);

        return response()->json($aktivita);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'report_date' => 'required|date',
            'bidang_id' => 'nullable|exists:bidangs,id',
            'time' => 'nullable|date_format:H:i',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'lokasi_id' => 'nullable|exists:lokasis,id',
            'loket_id' => 'nullable|exists:lokets,id',
            'jenis_layanan_id' => 'nullable|exists:jenis_layanans,id',
            'nib' => 'nullable|string|max:64',
            'applicant_name' => 'nullable|string|max:255',
            'gender' => 'nullable|string|max:4',
            'company' => 'nullable|string|max:255',
            'company_address' => 'nullable|string',
            'phone' => 'nullable|string|max:64',
            'email' => 'nullable|string|max:255',
            'purpose' => 'nullable|string|max:255',
            'complaint' => 'nullable|string',
            'solution' => 'nullable|string',
            'violations_count' => 'nullable|integer|min:0',
        ]);

        $user = $request->user();
        $bidangId = $data['bidang_id'] ?? $user->bidang_id;

        $title = $this->titleForBidang($bidangId, $data['report_date']);

        $report = DB::transaction(function () use ($user, $data, $bidangId, $title) {
            $report = Report::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'category_id' => $data['category_id'],
                    'report_date' => $data['report_date'],
                ],
                [
                    'bidang_id' => $bidangId,
                    'title' => $title,
                    'status' => 'draft',
                ]
            );
            $dirty = false;
            if (! $report->bidang_id && $bidangId) {
                $report->bidang_id = $bidangId;
                $dirty = true;
            }
            if ($report->title !== $title) {
                $report->title = $title;
                $dirty = true;
            }
            if (isset($data['violations_count'])) {
                $report->violations_count = (int) $data['violations_count'];
                $dirty = true;
            }
            if ($dirty) {
                $report->save();
            }
            return $report;
        });

        $itemData = collect($data)->only([
            'category_id', 'time', 'location', 'notes', 'lokasi_id', 'loket_id', 'jenis_layanan_id',
            'nib', 'applicant_name', 'gender', 'company', 'company_address', 'phone', 'email',
            'purpose', 'complaint', 'solution',
        ])->toArray();
        $itemData['report_id'] = $report->id;
        $itemData['sort_order'] = ($report->items()->max('sort_order') ?? 0) + 1;

        $item = ReportItem::create($itemData);

        return response()->json(
            $item->load(['category', 'lokasi:id,name', 'loket:id,name', 'jenisLayanan:id,name', 'photos', 'report:id,report_date,bidang_id,violations_count', 'report.bidang:id,name']),
            201
        );
    }

    public function update(Request $request, ReportItem $aktivita): JsonResponse
    {
        $this->authorizeAccess($request, $aktivita, write: true);

        $data = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'report_date' => 'sometimes|date',
            'bidang_id' => 'nullable|exists:bidangs,id',
            'time' => 'nullable|date_format:H:i',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'lokasi_id' => 'nullable|exists:lokasis,id',
            'loket_id' => 'nullable|exists:lokets,id',
            'jenis_layanan_id' => 'nullable|exists:jenis_layanans,id',
            'nib' => 'nullable|string|max:64',
            'applicant_name' => 'nullable|string|max:255',
            'gender' => 'nullable|string|max:4',
            'company' => 'nullable|string|max:255',
            'company_address' => 'nullable|string',
            'phone' => 'nullable|string|max:64',
            'email' => 'nullable|string|max:255',
            'purpose' => 'nullable|string|max:255',
            'complaint' => 'nullable|string',
            'solution' => 'nullable|string',
            'violations_count' => 'nullable|integer|min:0',
        ]);

        $aktivita->loadMissing('report');
        $currentReport = $aktivita->report;
        abort_unless($currentReport, 404, 'Laporan parent tidak ditemukan.');

        $currentDateStr = $currentReport->report_date instanceof \Illuminate\Support\Carbon
            ? $currentReport->report_date->format('Y-m-d')
            : substr((string) $currentReport->report_date, 0, 10);

        $newDateStr = isset($data['report_date'])
            ? \Illuminate\Support\Carbon::parse($data['report_date'])->format('Y-m-d')
            : $currentDateStr;

        $newCategoryId = (int) ($data['category_id'] ?? $aktivita->category_id);
        $newBidangId = $data['bidang_id'] ?? $currentReport->bidang_id ?? $request->user()->bidang_id;

        $newTitle = $this->titleForBidang($newBidangId, $newDateStr);

        if ($newDateStr !== $currentDateStr || $newCategoryId !== (int) $currentReport->category_id) {
            $target = Report::firstOrCreate(
                [
                    'user_id' => $currentReport->user_id,
                    'category_id' => $newCategoryId,
                    'report_date' => $newDateStr,
                ],
                [
                    'bidang_id' => $newBidangId,
                    'title' => $newTitle,
                    'status' => 'draft',
                ]
            );
            $targetDirty = false;
            if (! $target->bidang_id && $newBidangId) {
                $target->bidang_id = $newBidangId;
                $targetDirty = true;
            }
            if ($target->title !== $newTitle) {
                $target->title = $newTitle;
                $targetDirty = true;
            }
            if ($targetDirty) {
                $target->save();
            }
            $aktivita->report_id = $target->id;
            $aktivita->category_id = $newCategoryId;
        } else {
            $reportDirty = false;
            if ($currentReport->title !== $newTitle) {
                $currentReport->title = $newTitle;
                $reportDirty = true;
            }
            if (array_key_exists('bidang_id', $data) && (int) $currentReport->bidang_id !== (int) $newBidangId) {
                $currentReport->bidang_id = $newBidangId;
                $reportDirty = true;
            }
            if (array_key_exists('violations_count', $data)) {
                $currentReport->violations_count = (int) $data['violations_count'];
                $reportDirty = true;
            }
            if ($reportDirty) {
                $currentReport->save();
            }
        }

        $patch = collect($data)->only([
            'time', 'location', 'notes', 'lokasi_id', 'loket_id', 'jenis_layanan_id',
            'nib', 'applicant_name', 'gender', 'company', 'company_address', 'phone', 'email',
            'purpose', 'complaint', 'solution',
        ])->toArray();
        if (isset($data['category_id'])) {
            $patch['category_id'] = $data['category_id'];
        }
        $aktivita->fill($patch)->save();

        return response()->json(
            $aktivita->fresh()->load(['category', 'lokasi:id,name', 'loket:id,name', 'jenisLayanan:id,name', 'photos', 'report:id,report_date,bidang_id,violations_count', 'report.bidang:id,name'])
        );
    }

    public function destroy(Request $request, ReportItem $aktivita): JsonResponse
    {
        $this->authorizeAccess($request, $aktivita, write: true);

        foreach ($aktivita->photos as $photo) {
            Storage::disk('public')->delete($photo->path);
        }

        $reportId = $aktivita->report_id;
        $aktivita->delete();

        // cleanup empty parent report
        $remaining = ReportItem::where('report_id', $reportId)->count();
        if ($remaining === 0) {
            Report::where('id', $reportId)->delete();
        }

        return response()->json(['message' => 'deleted']);
    }

    public function uploadPhoto(Request $request, ReportItem $aktivita): JsonResponse
    {
        $this->authorizeAccess($request, $aktivita, write: true);

        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:8192',
        ]);

        $file = $request->file('photo');
        $path = $file->store('reports/' . $aktivita->report_id, 'public');

        $photo = ReportItemPhoto::create([
            'report_item_id' => $aktivita->id,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'sort_order' => ($aktivita->photos()->max('sort_order') ?? 0) + 1,
        ]);

        return response()->json($photo, 201);
    }

    public function deletePhoto(Request $request, ReportItem $aktivita, ReportItemPhoto $photo): JsonResponse
    {
        $this->authorizeAccess($request, $aktivita, write: true);
        abort_unless($photo->report_item_id === $aktivita->id, 404);

        Storage::disk('public')->delete($photo->path);
        $photo->delete();

        return response()->json(['message' => 'deleted']);
    }

protected function authorizeAccess(Request $request, ReportItem $item, bool $write = false): void
    {
        $user = $request->user();
        $item->loadMissing('report:id,user_id');
        if ($user->isAdmin()) {
            return;
        }
        abort_unless($item->report?->user_id === $user->id, 403, 'Tidak diizinkan.');
    }

    protected function titleForBidang(?int $bidangId, string|\DateTimeInterface|null $date = null): string
    {
        $bidangName = $bidangId ? Bidang::where('id', $bidangId)->value('name') : null;
        $base = 'LAPORAN HARIAN ' . mb_strtoupper($bidangName ?: 'DPMPTSP');

        if (! $date) {
            return $base;
        }

        try {
            $dt = $date instanceof \DateTimeInterface
                ? \Illuminate\Support\Carbon::instance($date)
                : \Illuminate\Support\Carbon::parse($date);
        } catch (\Throwable $e) {
            return $base;
        }

        $months = [
            1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL',
            5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS',
            9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER',
        ];

        return $base . ' ' . $dt->day . ' ' . $months[(int) $dt->month] . ' ' . $dt->year;
    }
}
