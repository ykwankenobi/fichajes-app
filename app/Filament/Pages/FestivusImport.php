<?php

namespace App\Filament\Pages;

use App\Models\CompanySetting;
use App\Models\Holiday;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\Http;

class FestivusImport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'Importar calendario laboral';
    protected string $view = 'filament.pages.festivus-import';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'municipality' => CompanySetting::current()->holiday_municipality_ine,
            'year' => now()->year,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('Calendario laboral')
                ->description('Busca cualquier municipio de España e importa sus festivos nacionales, autonómicos y locales desde festivos.io.')
                ->schema([
                    Select::make('municipality')
                        ->label('Municipio')
                        ->searchable()
                        ->required()
                        ->getSearchResultsUsing(function (string $search): array {
                            if (mb_strlen(trim($search)) < 2) {
                                return [];
                            }

                            return collect(Http::timeout(10)->get('https://festivos.io/v1/ref/search.json')->json('municipalities', []))
                                ->filter(fn (array $municipality): bool => str_contains(mb_strtolower($municipality['name'] . ' ' . $municipality['province']), mb_strtolower(trim($search))))
                                ->take(50)
                                ->mapWithKeys(fn (array $municipality): array => [$municipality['ine'] => $municipality['name'] . ' (' . $municipality['province'] . ')'])
                                ->all();
                        })
                        ->getOptionLabelUsing(function (?string $value): ?string {
                            if (! $value) {
                                return null;
                            }

                            $municipality = collect(Http::timeout(10)->get('https://festivos.io/v1/ref/search.json')->json('municipalities', []))->firstWhere('ine', $value);

                            return $municipality ? $municipality['name'] . ' (' . $municipality['province'] . ')' : $value;
                        }),
                    Select::make('year')
                        ->label('Año')
                        ->options(array_combine(range(now()->year - 2, now()->year + 2), range(now()->year - 2, now()->year + 2)))
                        ->default(now()->year)
                        ->required(),
                ])->columns(2),
        ]);
    }

    public function import(): void
    {
        $state = $this->form->getState();
        $ine = $state['municipality'] ?? null;
        $year = (int) ($state['year'] ?? now()->year);

        if (! preg_match('/^\d{5}$/', (string) $ine)) {
            Notification::make()->title('Selecciona un municipio válido')->danger()->send();
            return;
        }

        $response = Http::timeout(20)->get("https://festivos.io/v1/{$year}/municipio/{$ine}.json");

        if ($response->failed()) {
            Notification::make()->title('No hay calendario disponible para ese municipio y año')->body("festivos.io respondió con HTTP {$response->status()}.")->danger()->send();
            return;
        }

        $payload = $response->json();
        $municipality = $payload['municipality']['name'] ?? $ine;
        $imported = 0;

        foreach ($payload['holidays'] ?? [] as $holiday) {
            $names = $holiday['name'] ?? [];
            $name = $names['es'] ?? (array_values($names)[0] ?? 'Festivo');
            $level = $holiday['level'] ?? 'unknown';
            $source = $holiday['source']['ref'] ?? null;
            $notes = "Importado de festivos.io ({$level}) — {$municipality} {$year}" . ($source ? " — Fuente: {$source}" : '');

            Holiday::updateOrCreate(
                ['date' => $holiday['date']],
                ['name' => $name, 'notes' => $notes],
            );
            $imported++;
        }

        CompanySetting::current()->update(['holiday_municipality_ine' => $ine]);
        Notification::make()->title("{$imported} festivos importados")->body("{$municipality} — {$year}")->success()->send();
    }
}
