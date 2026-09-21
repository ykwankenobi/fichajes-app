<?php

namespace App\Console\Commands;

use App\Models\AbsenceRequest;
use App\Models\Holiday;
use App\Models\User;
use App\Models\WorkTimeRecord;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateSyntheticAttendance extends Command
{
    protected $signature = 'fichajes:generate-attendance {email=joaquin@elcos.es} {--from=} {--to=}';
    protected $description = 'Genera fichajes diarios según la configuración del empleado';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->firstOrFail();
        $from = Carbon::parse($this->option('from') ?: now()->startOfYear());
        $to = Carbon::parse($this->option('to') ?: now());
        $created = 0;
        for ($date = $from->copy()->startOfDay(); $date->lte($to); $date->addDay()) {
            $ranges = collect($user->scheduledRangesForDay($date));
            if ($ranges->isEmpty()) continue;
            if (Holiday::whereDate('date', $date)->exists()) continue;
            if (AbsenceRequest::where('user_id', $user->id)->where('status', 'approved')->whereDate('starts_at', '<=', $date)->whereDate('ends_at', '>=', $date)->exists()) continue;
            if (WorkTimeRecord::where('user_id', $user->id)->whereDate('started_at', $date)->exists()) continue;
            foreach ($ranges as $range) {
                $start = Carbon::parse($date->toDateString().' '.$range['desde'])->addMinutes(random_int(-10, 10));
                $end = Carbon::parse($date->toDateString().' '.$range['hasta'])->addMinutes(random_int(-10, 10));
                if ($end->lte($start)) continue;
                WorkTimeRecord::create(['user_id'=>$user->id,'record_type'=>WorkTimeRecord::TYPE_WORK,'started_at'=>$start,'ended_at'=>$end,'worked_minutes'=>$start->diffInMinutes($end),'justified_exit_minutes'=>0,'unjustified_exit_minutes'=>0]);
                $created++;
            }
        }
        $this->info("Fichajes creados: {$created}");
        return self::SUCCESS;
    }
}
