<?php

namespace App\Http\Controllers\Admin;

use App\Exports\WeeklyReportExport;
use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Models\User;
use App\Services\WorkTimeReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;

class WorkTimeReportController extends Controller
{
    public function weekly(
        Request $request,
        WorkTimeReportService $reportService
    ) {
        $week = $request->filled('week')
            ? $request->get('week')
            : now()->format('o-\WW');

        $selectedUserId = $request->get('user_id');
        $includeDailyDetails = $request->boolean('include_daily');

        $report = $reportService->getWeeklySummary(
            $request->user()->id,
            (bool) $request->user()->is_admin,
            $week,
            $selectedUserId
        );

        $dailyReport = $reportService->getWeeklyDailySummary(
            $request->user()->id,
            (bool) $request->user()->is_admin,
            $week,
            $selectedUserId
        );

        $users = User::query()
            ->where('is_admin', false)
            ->where('activo', true)
            ->orderBy('name')
            ->get();

        return view('admin.reports.weekly', compact(
            'report',
            'dailyReport',
            'week',
            'users',
            'selectedUserId'
        ));
    }

    public function exportWeekly(
        Request $request,
        WorkTimeReportService $reportService
    ) {
        $week = $request->filled('week')
            ? $request->get('week')
            : now()->format('o-\WW');

        $selectedUserId = $request->get('user_id');

        $report = $reportService->getWeeklySummary(
            $request->user()->id,
            (bool) $request->user()->is_admin,
            $week,
            $selectedUserId
        );

        $dailyReport = $reportService->getWeeklyDailySummary(
            $request->user()->id,
            (bool) $request->user()->is_admin,
            $week,
            $selectedUserId
        );

        return Excel::download(
            new WeeklyReportExport($report, $dailyReport),
            'informe-semanal-' . $week . '.csv',
            ExcelFormat::CSV
        );
    }

    public function exportDailyPdf(
        Request $request,
        WorkTimeReportService $reportService
    ) {
        $day = $request->filled('day')
            ? $request->get('day')
            : now()->format('Y-m-d');

        $selectedUserId = $request->get('user_id');

        $report = $reportService->getDailySummary(
            $request->user()->id,
            (bool) $request->user()->is_admin,
            $day,
            $selectedUserId
        );

        $corrections = $reportService->getDailyApprovedCorrections(
            $request->user()->id,
            (bool) $request->user()->is_admin,
            $day,
            $selectedUserId
        );

        $company = CompanySetting::current();

        $week = $day;

        $html = view('admin.reports.pdf.daily', compact(
            'report',
            'corrections',
            'company',
            'week',
            'selectedUserId'
        ))->render();

        return Pdf::loadHTML($html)
            ->setPaper('a4')
            ->stream('informe-diario-' . $day . '.pdf');
    }

    public function exportWeeklyPdf(
        Request $request,
        WorkTimeReportService $reportService
    ) {
        $week = $request->filled('week')
            ? $request->get('week')
            : now()->format('o-\WW');

        $selectedUserId = $request->get('user_id');

        $report = $reportService->getWeeklySummary(
            $request->user()->id,
            (bool) $request->user()->is_admin,
            $week,
            $selectedUserId
        );

        $dailyReport = $reportService->getWeeklyDailySummary(
            $request->user()->id,
            (bool) $request->user()->is_admin,
            $week,
            $selectedUserId
        );

        $corrections = $reportService->getWeeklyApprovedCorrections(
            $request->user()->id,
            (bool) $request->user()->is_admin,
            $week,
            $selectedUserId
        );

        $includeDailyDetails = $request->boolean('include_daily');

        $company = CompanySetting::current();

        $html = view('admin.reports.pdf.weekly', compact(
            'report',
            'dailyReport',
            'corrections',
            'company',
            'includeDailyDetails',
            'week',
            'selectedUserId'
        ))->render();

        return Pdf::loadHTML($html)
            ->setPaper('a4')
            ->stream('informe-semanal-' . $week . '.pdf');
    }

    public function exportMonthlyPdf(
        Request $request,
        WorkTimeReportService $reportService
    ) {
        $month = $request->filled('month')
            ? $request->get('month')
            : now()->format('Y-m');

        $selectedUserId = $request->get('user_id');

        $report = $reportService->getMonthlySummary(
            $request->user()->id,
            (bool) $request->user()->is_admin,
            $month,
            $selectedUserId
        );

        $dailyReport = $reportService->getMonthlyDailySummary(
            $request->user()->id,
            (bool) $request->user()->is_admin,
            $month,
            $selectedUserId
        );

        $corrections = $reportService->getMonthlyApprovedCorrections(
            $request->user()->id,
            (bool) $request->user()->is_admin,
            $month,
            $selectedUserId
        );

        $includeDailyDetails = $request->boolean('include_daily');

        $company = CompanySetting::current();

        $week = $month;

        $html = view('admin.reports.pdf.monthly', compact(
            'report',
            'dailyReport',
            'corrections',
            'company',
            'includeDailyDetails',
            'week',
            'selectedUserId'
        ))->render();

        return Pdf::loadHTML($html)
            ->setPaper('a4')
            ->stream('informe-mensual-' . $month . '.pdf');
    }
}
