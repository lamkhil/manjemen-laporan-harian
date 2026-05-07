<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $from = $request->date('from');
        $to = $request->date('to');
        $userScope = ! $request->user()->isAdmin();

        $reportsQ = Report::query();
        $itemsQ = ReportItem::query();

        if ($from) {
            $reportsQ->whereDate('report_date', '>=', $from);
            $itemsQ->whereHas('report', fn ($q) => $q->whereDate('report_date', '>=', $from));
        }
        if ($to) {
            $reportsQ->whereDate('report_date', '<=', $to);
            $itemsQ->whereHas('report', fn ($q) => $q->whereDate('report_date', '<=', $to));
        }
        if ($userScope) {
            $reportsQ->where('user_id', $request->user()->id);
            $itemsQ->whereHas('report', fn ($q) => $q->where('user_id', $request->user()->id));
        }

        $totalReports = (clone $reportsQ)->count();
        $totalItems = (clone $itemsQ)->count();
        $totalViolations = (clone $itemsQ)->whereHas('category', fn ($q) => $q->where('is_violation', true))->count();

        $byCategory = (clone $itemsQ)
            ->selectRaw('category_id, count(*) as total')
            ->groupBy('category_id')
            ->with('category:id,name,color')
            ->orderByDesc('total')
            ->get();

        $byDate = (clone $reportsQ)
            ->selectRaw('report_date, count(*) as total')
            ->groupBy('report_date')
            ->orderBy('report_date')
            ->get();

        return response()->json([
            'total_reports' => $totalReports,
            'total_items' => $totalItems,
            'total_violations' => $totalViolations,
            'by_category' => $byCategory,
            'by_date' => $byDate,
        ]);
    }
}
