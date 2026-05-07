<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
        $report->load(['user', 'items.category', 'items.photos']);

        $items = $report->items;
        $totalItems = $items->count();
        $violations = $items->filter(fn ($i) => $i->category?->is_violation)->count();
        $locations = $items->pluck('location')->filter()->unique()->count();

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

        return [
            'report' => $report,
            'items' => $items,
            'stats' => [
                'total_items' => $totalItems,
                'violations' => $violations,
                'locations' => $locations,
                'top_activity' => $top,
                'pkl' => $pkl,
                'baliho' => $baliho,
                'violation_pct' => $violationPct,
            ],
            'cat_bars' => $catBars,
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
}
