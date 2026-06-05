<?php

namespace App\Http\Controllers;

use App\Models\Bidang;
use App\Models\Report;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ReportPdfController extends Controller
{
    public function preview(Request $request, Report $report): Response
    {
        $this->authorizeAccess($request, $report);

        $data = $this->payload($report);

        return response()->view('pdf.report', $data);
    }

    public function download(Request $request, Report $report): Response
    {
        $this->authorizeAccess($request, $report);

        $data = $this->payload($report);

        $pdf = Pdf::loadView('pdf.report', $data)
            ->setPaper('a4', 'portrait')
            ->setOption(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'dpi' => 110]);

        $name = 'laporan-' . $report->report_date->format('Y-m-d') . '-' . $report->id . '.pdf';

        return $pdf->stream($name);
    }

    protected function payload(Report $report): array
    {
        $report->load([
            'user',
            'category',
            'items.category',
            'items.lokasi:id,name',
            'items.loket:id,name',
            'items.photos',
        ]);

        $items = $report->items;
        $totalItems = $items->count();
        $violations = (int) ($report->violations_count ?? 0);
        $locations = $items->pluck('location')->filter()->unique()->count();
        $isService = (bool) ($report->category?->is_service);

        $bySlug = $items->groupBy(fn ($i) => $i->category?->slug);
        $pkl = $bySlug->get('penertiban-pedagang-liar', collect())->count();
        $baliho = $bySlug->get('baliho-banner-liar', collect())->count();

        $byCategory = $items->groupBy(fn ($i) => $i->category?->name ?? '-')
            ->map(fn ($g) => $g->count())
            ->sortDesc();

        $top = $byCategory->keys()->first() ?? '-';

        $maxCat = $byCategory->values()->max() ?: 1;
        $catBars = $byCategory->take(8)->map(fn ($count, $name) => [
            'name' => $name,
            'count' => $count,
            'pct' => max(6, intval($count / $maxCat * 100)),
        ])->values();

        $violationPct = $totalItems > 0 ? intval(round($violations / $totalItems * 100)) : 0;

        $hari = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
        ];
        $bulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $d = $report->report_date instanceof Carbon ? $report->report_date : Carbon::parse($report->report_date);
        $tanggalLabel = sprintf(
            'Hari %s, tanggal %d %s %d',
            $hari[$d->format('l')] ?? $d->format('l'),
            $d->day,
            $bulan[$d->month] ?? $d->format('F'),
            $d->year,
        );

        $jam = '';
        if ($report->time_start || $report->time_end) {
            $jam = ' Pukul ' . substr((string) $report->time_start, 0, 5) . ' - ' . substr((string) $report->time_end, 0, 5);
        }

        $male = $items->where('gender', 'L')->count();
        $female = $items->where('gender', 'P')->count();
        $companies = $items->pluck('company')->filter()->unique()->count();
        $byPurpose = $items->groupBy(fn ($i) => $i->purpose ?: '-')
            ->map(fn ($g) => $g->count())
            ->sortDesc();
        $byLoket = $items->groupBy(fn ($i) => $i->loket?->name ?: '-')
            ->map(fn ($g) => $g->count())
            ->sortDesc();
        $maxPurpose = $byPurpose->values()->max() ?: 1;
        $purposeBars = $byPurpose->take(8)->map(fn ($count, $name) => [
            'name' => $name,
            'count' => $count,
            'pct' => max(6, intval($count / $maxPurpose * 100)),
        ])->values();

        return [
            'report' => $report,
            'items' => $items,
            'is_service' => $isService,
            'stats' => [
                'total_items' => $totalItems,
                'violations' => $violations,
                'locations' => $locations,
                'top_activity' => $top,
                'pkl' => $pkl,
                'baliho' => $baliho,
                'violation_pct' => $violationPct,
                'male' => $male,
                'female' => $female,
                'companies' => $companies,
                'by_loket' => $byLoket,
                'top_purpose' => $byPurpose->keys()->first() ?? '-',
            ],
            'cat_bars' => $catBars,
            'purpose_bars' => $purposeBars,
            'tanggal_label' => $tanggalLabel,
            'jam_label' => $jam,
        ];
    }

    protected function authorizeAccess(Request $request, Report $report): void
    {
        $user = $request->user();
        if ($user?->isAdmin()) {
            return;
        }
        abort_unless($report->user_id === $user?->id, 403);
    }

    public function rekapPreview(Request $request): Response
    {
        $data = $this->rekapPayload($request);
        return response()->view('pdf.rekap', $data);
    }

    public function rekapDownload(Request $request): Response
    {
        $data = $this->rekapPayload($request);
        $pdf = Pdf::loadView('pdf.rekap', $data)
            ->setPaper('a4', 'portrait')
            ->setOption(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'dpi' => 110]);

        $from = $data['from']?->format('Ymd');
        $to = $data['to']?->format('Ymd');
        $name = $from === $to ? "rekap-harian-$from.pdf" : "rekap-$from-sd-$to.pdf";

        return $pdf->stream($name);
    }

    protected function rekapPayload(Request $request): array
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Hanya admin.');

        $from = $request->date('from') ?: Carbon::today();
        $to = $request->date('to') ?: $from;

        $reports = Report::query()
            ->with([
                'user:id,name',
                'bidang:id,name',
                'category:id,name,is_service,is_violation',
                'items.category',
                'items.loket:id,name',
                'items.lokasi:id,name',
                'items.jenisLayanan:id,name',
                'items.photos',
            ])
            ->whereBetween('report_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('report_date')
            ->orderBy('bidang_id')
            ->orderBy('id')
            ->get();

        $byBidang = $reports->groupBy(fn ($r) => $r->bidang?->name ?? '— Tanpa Bidang —');

        $totalReports = $reports->count();
        $totalItems = $reports->sum(fn ($r) => $r->items->count());
        $totalViolations = $reports->sum('violations_count');
        $serviceReports = $reports->filter(fn ($r) => $r->category?->is_service)->count();

        $hari = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
        ];
        $bulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $fmt = fn (Carbon $d) => sprintf('%s, %d %s %d', $hari[$d->format('l')] ?? '', $d->day, $bulan[$d->month] ?? '', $d->year);

        return [
            'from' => $from,
            'to' => $to,
            'period_label' => $from->equalTo($to) ? $fmt($from) : ($fmt($from) . ' s.d. ' . $fmt($to)),
            'reports' => $reports,
            'by_bidang' => $byBidang,
            'totals' => [
                'reports' => $totalReports,
                'items' => $totalItems,
                'violations' => $totalViolations,
                'service' => $serviceReports,
            ],
        ];
    }

    public function rekapBidangPreview(Request $request, Bidang $bidang): Response
    {
        $data = $this->rekapBidangPayload($request, $bidang);
        return response()->view('pdf.rekap_bidang', $data);
    }

    public function rekapBidangDownload(Request $request, Bidang $bidang): Response
    {
        $data = $this->rekapBidangPayload($request, $bidang);
        $pdf = Pdf::loadView('pdf.rekap_bidang', $data)
            ->setPaper('a4', 'portrait')
            ->setOption(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'dpi' => 110]);

        $slug = Str::slug($bidang->name);
        $from = $data['from']?->format('Ymd');
        $to = $data['to']?->format('Ymd');
        $name = $from === $to ? "rekap-$slug-$from.pdf" : "rekap-$slug-$from-sd-$to.pdf";

        return $pdf->stream($name);
    }

    protected function rekapBidangPayload(Request $request, Bidang $bidang): array
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Hanya admin.');

        $from = $request->date('from') ?: Carbon::today();
        $to = $request->date('to') ?: $from;

        $reports = Report::query()
            ->with([
                'user:id,name',
                'category:id,name,is_service,is_violation',
                'items.category',
                'items.loket:id,name',
                'items.lokasi:id,name',
                'items.jenisLayanan:id,name',
                'items.photos',
            ])
            ->where('bidang_id', $bidang->id)
            ->whereBetween('report_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('report_date')
            ->orderBy('id')
            ->get();

        $allItems = $reports->flatMap->items;

        $byLoket = $allItems->groupBy(fn ($i) => $i->loket?->name ?: '-')->map->count()->sortDesc();
        $byJenis = $allItems->groupBy(fn ($i) => $i->jenisLayanan?->name ?: ($i->purpose ?: '-'))->map->count()->sortDesc();
        $byCategory = $reports->groupBy(fn ($r) => $r->category?->name ?: '-');

        $totalItems = $allItems->count();
        $totalViolations = $reports->sum('violations_count');

        $hari = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
        ];
        $bulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $fmt = fn (Carbon $d) => sprintf('%s, %d %s %d', $hari[$d->format('l')] ?? '', $d->day, $bulan[$d->month] ?? '', $d->year);

        return [
            'bidang' => $bidang,
            'from' => $from,
            'to' => $to,
            'period_label' => $from->equalTo($to) ? $fmt($from) : ($fmt($from) . ' s.d. ' . $fmt($to)),
            'reports' => $reports,
            'all_items' => $allItems,
            'by_loket' => $byLoket,
            'by_jenis' => $byJenis,
            'by_category' => $byCategory,
            'totals' => [
                'items' => $totalItems,
                'violations' => $totalViolations,
                'reports' => $reports->count(),
            ],
        ];
    }
}
