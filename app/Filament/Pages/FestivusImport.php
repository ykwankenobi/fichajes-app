<?php

namespace App\Filament\Pages;

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
    protected static ?string $navigationLabel = 'Importar festivos';
    protected static ?string $title = 'Importar festivos desde Festivus';
    protected static ?int $navigationSort = 26;
    protected string $view = 'filament.pages.festivus-import';

    public ?array $data = [];

    private const LOCATIONS = [
        'sevilla' => ['label' => 'Sevilla (Andalucía)', 'path' => 'España/Andalucía/Sevilla'],
        'barcelona' => ['label' => 'Barcelona (Cataluña)', 'path' => 'España/Cataluña/Barcelona'],
        'madrid' => ['label' => 'Madrid (Comunidad de Madrid)', 'path' => 'España/Comunidad de Madrid/Madrid'],
        'betera' => ['label' => 'Bétera (Comunitat Valenciana)', 'path' => 'España/Comunitat Valenciana/Betera'],
        'burjassot' => ['label' => 'Burjassot (Comunitat Valenciana)', 'path' => 'España/Comunitat Valenciana/Burjassot'],
        'paterna' => ['label' => 'Paterna (Comunitat Valenciana)', 'path' => 'España/Comunitat Valenciana/Paterna'],
        'palma' => ['label' => 'Palma (Illes Balears)', 'path' => 'España/Illes Balears/Palma'],
        'soller' => ['label' => 'Sóller (Illes Balears)', 'path' => 'España/Illes Balears/Sóller'],
    ];

    public function mount(): void
    {
        $this->form->fill(['location' => 'madrid', 'year' => now()->year]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('Calendario laboral')
                ->description('Importa los festivos estatales, autonómicos y locales publicados por Festivus. Después podrás editarlos desde la sección Festivos.')
                ->schema([
                    Select::make('location')
                        ->label('Ciudad')
                        ->options(collect(self::LOCATIONS)->mapWithKeys(fn (array $location, string $key): array => [$key => $location['label']])->all())
                        ->required(),
                    Select::make('year')
                        ->label('Año')
                        ->options(array_combine(range(now()->year - 1, now()->year + 2), range(now()->year - 1, now()->year + 2)))
                        ->default(now()->year)
                        ->required(),
                ])->columns(2),
        ]);
    }

    public function import(): void
    {
        $state = $this->form->getState();
        $location = self::LOCATIONS[$state['location']] ?? null;

        if (! $location) {
            Notification::make()->title('Selecciona una ciudad válida')->danger()->send();
            return;
        }

        $year = (int) $state['year'];
        $segments = array_map('rawurlencode', explode('/', $location['path']));
        $url = 'https://raw.githubusercontent.com/festivus-es/festivus/master/data/' . implode('/', $segments) . '/' . $year . '.cal';
        $response = Http::timeout(20)->get($url);

        if ($response->failed()) {
            Notification::make()->title('No hay calendario disponible para esa ciudad y año')->body("Festivus respondió con HTTP {$response->status()}.")->danger()->send();
            return;
        }

        $imported = 0;
        foreach (preg_split('/\r?\n/', $response->body()) as $line) {
            if (! preg_match('/^(\d{4}-\d{2}-\d{2})\s+(.+)$/', trim($line), $matches)) {
                continue;
            }

            $parts = preg_split('/\s+\(([^()]*)\)$/', $matches[2], -1, PREG_SPLIT_DELIM_CAPTURE);
            $name = trim($parts[0]);
            $category = $parts[1] ?? null;
            $notes = 'Importado de Festivus' . ($category ? " ({$category})" : '') . " — {$location['label']} {$year}";

            Holiday::updateOrCreate(
                ['date' => $matches[1]],
                ['name' => $name, 'notes' => $notes],
            );
            $imported++;
        }

        Notification::make()->title("{$imported} festivos importados")->body("{$location['label']} — {$year}")->success()->send();
    }
}
