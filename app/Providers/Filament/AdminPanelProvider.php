<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\AdminStatsOverview;
use App\Filament\Widgets\AttendanceCalendar;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $colors = [
            'amber' => Color::Amber,
            'blue' => Color::Blue,
            'green' => Color::Green,
            'indigo' => Color::Indigo,
            'red' => Color::Red,
            'sky' => Color::Sky,
        ];
        $primaryColor = $colors[config('branding.primary_color')] ?? Color::Red;
        $logo = config('branding.logo');

        $panel = $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->maxContentWidth(Width::Full)
            ->plugin(FilamentFullCalendarPlugin::make()
                ->locale('es')
                ->timezone(config('app.timezone')))
            ->colors([
                'primary' => $primaryColor,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AdminStatsOverview::class,
                AttendanceCalendar::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);

        if ($logo && file_exists(public_path($logo))) {
            $panel
                ->brandLogo(asset($logo))
                ->brandLogoHeight('3rem');
        }

        return $panel;
    }
}
