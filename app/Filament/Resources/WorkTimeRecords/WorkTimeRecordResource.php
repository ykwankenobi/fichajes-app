<?php

namespace App\Filament\Resources\WorkTimeRecords;

use App\Filament\Resources\WorkTimeRecords\Pages\CreateWorkTimeRecord;
use App\Filament\Resources\WorkTimeRecords\Pages\EditWorkTimeRecord;
use App\Filament\Resources\WorkTimeRecords\Pages\ListWorkTimeRecords;
use App\Filament\Resources\WorkTimeRecords\Schemas\WorkTimeRecordForm;
use App\Filament\Resources\WorkTimeRecords\Tables\WorkTimeRecordsTable;
use App\Models\WorkTimeRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WorkTimeRecordResource extends Resource
{
    protected static ?string $model = WorkTimeRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $slug = 'work-time-incidents';

    protected static ?string $modelLabel = 'incidencia de fichaje';

    protected static ?string $pluralModelLabel = 'incidencias de fichajes';

    protected static ?string $navigationLabel = 'Incidencias de fichajes';

    protected static ?int $navigationSort = 30;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function (Builder $query): void {
                $query->where('requires_review', true)
                    ->orWhere('closed_automatically', true)
                    ->orWhere('unjustified_exit_minutes', '>', 0)
                    ->orWhere('record_type', WorkTimeRecord::TYPE_UNJUSTIFIED_EXIT)
                    ->orWhere('end_type', WorkTimeRecord::TYPE_UNJUSTIFIED_EXIT);
            });
    }

    public static function getNavigationBadge(): ?string
    {
        $count = WorkTimeRecord::query()
            ->where('requires_review', true)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return WorkTimeRecordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkTimeRecordsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkTimeRecords::route('/'),
            'create' => CreateWorkTimeRecord::route('/create'),
            'edit' => EditWorkTimeRecord::route('/{record}/edit'),
        ];
    }
}
