<?php

namespace App\Console\Commands;

use App\Models\WorkTimeRecord;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CloseOpenWorkTimeRecords extends Command
{
    protected $signature = 'fichajes:close-open-records';

    protected $description = 'Cierra automáticamente fichajes abiertos antiguos';

    public function handle(): int
    {
        $records = WorkTimeRecord::query()
            ->whereNull('ended_at')
            ->whereDate('started_at', '<', today())
            ->get();

        foreach ($records as $record) {
            $endedAt = Carbon::parse($record->started_at)
                ->endOfDay();

            $minutes = (int) $record->started_at
                ->diffInMinutes($endedAt);

            $data = [
                'ended_at' => $endedAt,
                'closed_automatically' => true,
                'requires_review' => true,
            ];

            switch ($record->record_type) {
                case WorkTimeRecord::TYPE_WORK:
                    $data['worked_minutes'] = $minutes;
                    break;

                case WorkTimeRecord::TYPE_JUSTIFIED_EXIT:
                    $data['justified_exit_minutes'] = $minutes;
                    break;

                case WorkTimeRecord::TYPE_UNJUSTIFIED_EXIT:
                    $data['unjustified_exit_minutes'] = $minutes;
                    break;
            }

            $record->update($data);

            $this->info(
                "Registro {$record->id} cerrado automáticamente."
            );
        }

        $this->info('Proceso finalizado.');

        return self::SUCCESS;
    }
}