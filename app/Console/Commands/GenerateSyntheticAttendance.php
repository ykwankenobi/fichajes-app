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
        $days = collect($user->working_days ?: []);
        $ranges = collect($user->horario_franjas ?: []);
        if ($ranges->isEmpty()) { $this->error('El empleado no tiene franjas horarias.'); return self::FAILURE; }
        $created = 0;
        for ($date = $from->copy()->startOfDay(); $date->lte($to); $date->addDay()) {
            if (! $days->contains(strtolower($date->format('l')))) continue;
            if (Holiday::whereDate('date', $date)->exists()) continue;
            if (AbsenceRequest::where('user_id', $user->id)->where('status', 'approved')->whereDate('starts_at', '<=', $date)->whereDate('ends_at', '>=', $date)->exists()) continue;
            if (WorkTimeRecord::where('user_id', $user->id)->whereDate('started_at', $date)->exists()) continue;
            $range = $ranges->first();
            $start = Carbon::parse($date->toDateString().' '.$range['desde'])->addMinutes(random_int(-10, 10));
            $end = Carbon::parse($date->toDateString().' '.$range['hasta'])->addMinutes(random_int(-10, 10));
            WorkTimeRecord::create(['user_id'=>$user->id,'record_type'=>WorkTimeRecord::TYPE_WORK,'started_at'=>$start,'ended_at'=>$end,'worked_minutes'=>$start->diffInMinutes($end),'justified_exit_minutes'=>0,'unjustified_exit_minutes'=>0]);
            $created++;
        }
        $this->info("Fichajes creados: {$created}");
        return self::SUCCESS;
    }
}
