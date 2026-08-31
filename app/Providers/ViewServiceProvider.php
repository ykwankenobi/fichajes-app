<?php

namespace App\Providers;

use App\Models\AbsenceRequest;
use App\Models\WorkTimeRecord;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('*', function ($view) {
            $pendingAbsencesCount = 0;
            $userReviewedAbsencesCount = 0;
            $pendingWorkTimeIncidentsCount = 0;

            if (auth()->check()) {
                if (auth()->user()->can('view-admin-panel')) {
                    $pendingAbsencesCount = AbsenceRequest::query()
                        ->where('status', 'pending')
                        ->count();

                    $pendingWorkTimeIncidentsCount = WorkTimeRecord::query()
                        ->where('requires_review', true)
                        ->count();
                }

                $userReviewedAbsencesCount = auth()->user()
                    ->absenceRequests()
                    ->whereIn('status', ['approved', 'rejected'])
                    ->whereNotNull('reviewed_at')
                    ->whereNull('review_notification_read_at')
                    ->count();
            }

            $view->with([
                'pendingAbsencesCount' => $pendingAbsencesCount,
                'userReviewedAbsencesCount' => $userReviewedAbsencesCount,
                'pendingWorkTimeIncidentsCount' => $pendingWorkTimeIncidentsCount,
            ]);
        });
    }
}