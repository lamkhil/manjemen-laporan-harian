<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportItem;
use App\Models\ReportItemPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = Report::query()
            ->with(['user:id,name,unit_kerja,jabatan', 'category:id,name,color,is_service', 'bidang:id,name'])
            ->withCount('items')
            ->orderByDesc('report_date')
            ->orderByDesc('id');

        if ($bid = $request->integer('bidang_id')) {
            $q->where('bidang_id', $bid);
        }

        if ($from = $request->date('from')) {
            $q->whereDate('report_date', '>=', $from);
        }
        if ($to = $request->date('to')) {
            $q->whereDate('report_date', '<=', $to);
        }
        if ($date = $request->date('date')) {
            $q->whereDate('report_date', $date);
        }
        if ($status = $request->string('status')->toString()) {
            $q->where('status', $status);
        }
        if ($userId = $request->integer('user_id')) {
            $q->where('user_id', $userId);
        }
        if (! $request->user()->isAdmin() && ! $request->boolean('all')) {
            $q->where('user_id', $request->user()->id);
        }

        $perPage = (int) $request->integer('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        return response()->json($q->paginate($perPage));
    }

    public function show(Request $request, Report $report): JsonResponse
    {
        $this->authorizeAccess($request, $report);

        $report->load([
            'user:id,name,unit_kerja,jabatan',
            'category:id,name,color,is_service',
            'bidang:id,name',
            'items.category',
            'items.lokasi:id,name',
            'items.loket:id,name',
            'items.jenisLayanan:id,name',
            'items.photos',
        ]);

        return response()->json($report);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'bidang_id' => 'nullable|exists:bidangs,id',
            'report_date' => 'required|date',
            'time_start' => 'nullable|date_format:H:i',
            'time_end' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string',
            'violations_count' => 'nullable|integer|min:0',
            'status' => 'nullable|in:draft,final',
        ]);

        $data['user_id'] = $request->user()->id;
        $data['title'] = $data['title'] ?? 'LAPORAN HARIAN DPMPTSP';
        if (empty($data['bidang_id'])) {
            $data['bidang_id'] = $request->user()->bidang_id;
        }

        $report = Report::create($data);

        return response()->json($report->fresh()->load(['category:id,name,color,is_service', 'bidang:id,name']), 201);
    }

    public function update(Request $request, Report $report): JsonResponse
    {
        $this->authorizeAccess($request, $report, write: true);

        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'category_id' => 'sometimes|exists:categories,id',
            'bidang_id' => 'nullable|exists:bidangs,id',
            'report_date' => 'sometimes|date',
            'time_start' => 'nullable|date_format:H:i',
            'time_end' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string',
            'violations_count' => 'nullable|integer|min:0',
            'status' => 'nullable|in:draft,final',
        ]);

        $report->update($data);

        return response()->json($report->fresh()->load(['category:id,name,color,is_service', 'bidang:id,name']));
    }

    public function destroy(Request $request, Report $report): JsonResponse
    {
        $this->authorizeAccess($request, $report, write: true);

        DB::transaction(function () use ($report) {
            foreach ($report->items as $item) {
                foreach ($item->photos as $photo) {
                    Storage::disk('public')->delete($photo->path);
                }
            }
            $report->delete();
        });

        return response()->json(['message' => 'deleted']);
    }

    public function storeItem(Request $request, Report $report): JsonResponse
    {
        $this->authorizeAccess($request, $report, write: true);

        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'time' => 'nullable|date_format:H:i',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'sort_order' => 'nullable|integer',
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
        ]);

        $data['report_id'] = $report->id;
        if (! isset($data['sort_order'])) {
            $data['sort_order'] = ($report->items()->max('sort_order') ?? 0) + 1;
        }

        $item = ReportItem::create($data);

        return response()->json($item->load(['category', 'lokasi:id,name', 'loket:id,name', 'jenisLayanan:id,name', 'photos']), 201);
    }

    public function updateItem(Request $request, Report $report, ReportItem $item): JsonResponse
    {
        $this->authorizeAccess($request, $report, write: true);
        abort_unless($item->report_id === $report->id, 404);

        $data = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'time' => 'nullable|date_format:H:i',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'sort_order' => 'nullable|integer',
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
        ]);

        $item->update($data);

        return response()->json($item->load(['category', 'lokasi:id,name', 'loket:id,name', 'jenisLayanan:id,name', 'photos']));
    }

    public function destroyItem(Request $request, Report $report, ReportItem $item): JsonResponse
    {
        $this->authorizeAccess($request, $report, write: true);
        abort_unless($item->report_id === $report->id, 404);

        foreach ($item->photos as $photo) {
            Storage::disk('public')->delete($photo->path);
        }
        $item->delete();

        return response()->json(['message' => 'deleted']);
    }

    public function uploadPhoto(Request $request, Report $report, ReportItem $item): JsonResponse
    {
        $this->authorizeAccess($request, $report, write: true);
        abort_unless($item->report_id === $report->id, 404);

        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:8192',
        ]);

        $file = $request->file('photo');
        $path = $file->store('reports/' . $report->id, 'public');

        $photo = ReportItemPhoto::create([
            'report_item_id' => $item->id,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'sort_order' => ($item->photos()->max('sort_order') ?? 0) + 1,
        ]);

        return response()->json($photo, 201);
    }

    public function deletePhoto(Request $request, Report $report, ReportItem $item, ReportItemPhoto $photo): JsonResponse
    {
        $this->authorizeAccess($request, $report, write: true);
        abort_unless($item->report_id === $report->id && $photo->report_item_id === $item->id, 404);

        Storage::disk('public')->delete($photo->path);
        $photo->delete();

        return response()->json(['message' => 'deleted']);
    }

    protected function authorizeAccess(Request $request, Report $report, bool $write = false): void
    {
        $user = $request->user();
        if ($user->isAdmin()) {
            return;
        }
        abort_unless($report->user_id === $user->id, 403, 'Tidak diizinkan.');
    }
}
