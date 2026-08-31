<?php

namespace App\Filament\Resources\Holidays\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class HolidayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nombre')->required()->maxLength(255),
            DatePicker::make('date')->label('Fecha')->required()->native(false)->displayFormat('d/m/Y'),
            Textarea::make('notes')->label('Notas')->rows(3)->maxLength(1000),
        ]);
    }
}
