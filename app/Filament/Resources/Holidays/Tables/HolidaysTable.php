<?php

namespace App\Filament\Resources\Holidays\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HolidaysTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date')
            ->columns([
                TextColumn::make('date')->label('Fecha')->date('d/m/Y')->sortable(),
                TextColumn::make('name')->label('Festivo')->searchable()->sortable(),
                TextColumn::make('notes')->label('Notas')->limit(80)->toggleable(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
