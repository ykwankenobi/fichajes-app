<?php

namespace App\Filament\Resources\AbsenceRequests;

use App\Filament\Resources\AbsenceRequests\Pages\CreateAbsenceRequest;
use App\Filament\Resources\AbsenceRequests\Pages\EditAbsenceRequest;
use App\Filament\Resources\AbsenceRequests\Pages\ListAbsenceRequests;
use App\Filament\Resources\AbsenceRequests\Schemas\AbsenceRequestForm;
use App\Filament\Resources\AbsenceRequests\Tables\AbsenceRequestsTable;
use App\Models\AbsenceRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AbsenceRequestResource extends Resource
{
    protected static ?string $model = AbsenceRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $slug = 'absence-requests';

    protected static ?string $modelLabel = 'ausencia';

    protected static ?string $pluralModelLabel = 'ausencias';

    protected static ?string $navigationLabel = 'Ausencias';

    protected static ?int $navigationSort = 20;

    public static function getNavigationBadge(): ?string
    {
        $count = AbsenceRequest::query()
            ->where('status', 'pending')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return AbsenceRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AbsenceRequestsTable::configure($table);
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
            'index' => ListAbsenceRequests::route('/'),
            'create' => CreateAbsenceRequest::route('/create'),
            'edit' => EditAbsenceRequest::route('/{record}/edit'),
        ];
    }
}
